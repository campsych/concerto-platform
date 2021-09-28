<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Entity\AdministrationSetting;
use Concerto\PanelBundle\Entity\Message;
use Concerto\PanelBundle\Entity\Test;
use Concerto\PanelBundle\Entity\User;
use Concerto\PanelBundle\Repository\AdministrationSettingRepository;
use Concerto\PanelBundle\Repository\DataTableRepository;
use Concerto\PanelBundle\Repository\MessageRepository;
use Concerto\PanelBundle\Repository\TestRepository;
use Concerto\PanelBundle\Repository\TestSessionRepository;
use Concerto\PanelBundle\Repository\TestWizardRepository;
use Concerto\PanelBundle\Repository\ViewTemplateRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Templating\EngineInterface;
use Concerto\PanelBundle\Entity\TestSession;
use Concerto\PanelBundle\Repository\TestSessionLogRepository;
use Concerto\PanelBundle\Entity\TestSessionLog;
use Symfony\Component\Yaml\Yaml;
use DateTime;
use Concerto\PanelBundle\Repository\ScheduledTaskRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Concerto\APIBundle\Repository\ClientRepository;
use Symfony\Component\Process\Process;

class AdministrationService
{
    const OS_WIN = 0;
    const OS_LINUX = 1;

    private $settingsRepository;
    private $messagesRepository;
    private $configSettings;
    private $templating;
    private $testSessionLogRepository;
    private $doctrine;
    private $scheduledTaskRepository;
    private $rootDir;
    private $kernel;
    private $apiClientRepository;
    private $testRunnerSettings;
    private $testRepository;
    private $dataTableRepository;
    private $testWizardRepository;
    private $viewTemplateRepository;
    private $testSessionRepository;
    private $securityTokenStorage;

    public function __construct(
        AdministrationSettingRepository $settingsRepository,
        MessageRepository               $messageRepository,
                                        $configSettings,
                                        $version,
                                        $rootDir,
        EngineInterface                 $templating,
        TestSessionLogRepository        $testSessionLogRepository,
        RegistryInterface               $doctrine,
        ScheduledTaskRepository         $scheduledTaskRepository,
        KernelInterface                 $kernel,
        ClientRepository                $clientRepository,
                                        $testRunnerSettings,
        TestRepository                  $testRepository,
        DataTableRepository             $dataTableRepository,
        TestWizardRepository            $testWizardRepository,
        ViewTemplateRepository          $viewTemplateRepository,
        TestSessionRepository           $testSessionRepository,
        TokenStorageInterface           $securityTokenStorage
    )
    {
        $this->settingsRepository = $settingsRepository;
        $this->messagesRepository = $messageRepository;
        $this->configSettings = $configSettings;
        $this->configSettings["internal"]["version"] = $version;
        $this->templating = $templating;
        $this->testSessionLogRepository = $testSessionLogRepository;
        $this->doctrine = $doctrine;
        $this->scheduledTaskRepository = $scheduledTaskRepository;
        $this->rootDir = $rootDir;
        $this->kernel = $kernel;
        $this->apiClientRepository = $clientRepository;
        $this->testRunnerSettings = $testRunnerSettings;
        $this->testRepository = $testRepository;
        $this->dataTableRepository = $dataTableRepository;
        $this->testWizardRepository = $testWizardRepository;
        $this->viewTemplateRepository = $viewTemplateRepository;
        $this->testSessionRepository = $testSessionRepository;
        $this->securityTokenStorage = $securityTokenStorage;
    }

    public static function getOS()
    {
        $isWindows = preg_match('/^(windows|win32|winnt|cygwin)/i', PHP_OS);
        if ($isWindows) return self::OS_WIN;
        return self::OS_LINUX;
    }

    public function insertSessionLimitMessage(TestSession $session)
    {
        $msg = new Message();
        $msg->setCagegory(Message::CATEGORY_SYSTEM);
        $msg->setSubject("Session limit reached.");
        $content = $this->templating->render("ConcertoPanelBundle:Administration:msg_session_limit.html.twig", array(
            "limit" => $this->getSessionLimit()
        ));
        $msg->setMessage($content);
        $this->messagesRepository->save($msg);
    }

    private function fetchTestSessionLogs($start_time)
    {
        //this will only take latest 100 logs since this is max amount that we'll be showing in panel
        foreach ($this->testSessionLogRepository->findLatestNewerThan($start_time) as $log) {
            if ($log->getTest() === null)
                continue;

            $msg = new Message();
            $msg->setTime($log->getCreated());
            $msg->setCagegory(Message::CATEGORY_TEST);
            $error_source = "";
            switch ($log->getType()) {
                case TestSessionLog::TYPE_JS:
                    $error_source = "JS";
                    break;
                case TestSessionLog::TYPE_R:
                    $error_source = "R";
                    break;
            }
            $msg->setSubject("Test #" . $log->getTest()->getId() . ", $error_source error.");
            $content = $this->templating->render("ConcertoPanelBundle:Administration:msg_test_error.html.twig", array(
                "test_id" => $log->getTest()->getId(),
                "error_source" => $error_source,
                "error_message" => $log->getMessage()
            ));
            $msg->setMessage($content);
            $this->messagesRepository->save($msg);
        }
    }

    private function fetchFeed($url, $start_time)
    {
        if (!$this->isOnline())
            return;

        $raw_feed = file_get_contents($url);
        $feed = Yaml::parse($raw_feed);
        foreach ($feed["entries"] as $entry) {
            if ($entry["time"] <= $start_time)
                break;

            $msg = new Message();
            $dt = new DateTime();
            $dt->setTimestamp($entry["time"]);
            $msg->setTime($dt);
            $msg->setCagegory(Message::CATEGORY_GLOBAL);
            $msg->setSubject($entry["subject"]);
            $content = $this->templating->render("ConcertoPanelBundle:Administration:msg_feed.html.twig", array(
                "message" => $entry["message"]
            ));
            $msg->setMessage($content);
            $this->messagesRepository->save($msg);
        }
    }

    public function fetchMessagesCollection()
    {
        $this_fetch_time = time();
        $last_fetch_time = $this->getLastMessageFetchTime();
        if ($last_fetch_time === null)
            $last_fetch_time = 0;

        $this->fetchTestSessionLogs($last_fetch_time);

        $this->setSettings(array("last_message_fetch_time" => $this_fetch_time), false);
    }

    public function getMessagesCollection()
    {
        $this->fetchMessagesCollection();
        return $this->messagesRepository->findBy([], array("time" => "DESC"), 100);
    }

    public function deleteMessage($object_ids)
    {
        $object_ids = explode(",", $object_ids);
        $this->messagesRepository->deleteById($object_ids);
    }

    public function clearMessages()
    {
        $this->messagesRepository->deleteAll();
    }

    public function getExposedSettingsMap()
    {
        $map = $this->configSettings["exposed"];
        foreach ($map as $k => $v) {
            $map[$k] = (string)$v;
        }
        foreach ($this->settingsRepository->findAllExposed() as $setting) {
            if (isset($map[$setting->getKey() . "_overridable"]) && $map[$setting->getKey() . "_overridable"] === "false") {
                continue;
            }
            $map[$setting->getKey()] = $setting->getValue();
        }
        return $map;
    }

    public function getInternalSettingsMap()
    {
        //@TODO don't return git password
        $map = $this->configSettings["internal"];
        foreach ($map as $k => $v) {
            $map[$k] = (string)$v;
        }
        foreach ($this->settingsRepository->findAllInternal() as $setting) {
            $map[$setting->getKey()] = $setting->getValue();
        }
        return $map;
    }

    public function getAllSettingsMap()
    {
        $map = array_merge($this->getExposedSettingsMap(), $this->getInternalSettingsMap());
        return $map;
    }

    public function getSettingValue($key)
    {
        if (isset($this->testRunnerSettings[$key])) {
            return $this->testRunnerSettings[$key];
        }

        $map = $this->getAllSettingsMap();
        if (isset($map[$key])) {
            return $map[$key];
        }
        return null;
    }

    public function isApiEnabled()
    {
        $enabled = $this->getSettingValue("api_enabled");
        return $enabled === "true";
    }

    public function isDataApiEnabled()
    {
        $enabled = $this->getSettingValue("data_api_enabled");
        return $enabled === "true";
    }

    public function isFailedAuthLockEnabled()
    {
        $streak = $this->getFailedAuthLockStreak();
        $time = $this->getFailedAuthLockTime();

        return $streak && $time;
    }

    public function getFailedAuthLockStreak()
    {
        return $this->getSettingValue("failed_auth_lock_streak");
    }

    public function getFailedAuthLockTime()
    {
        return $this->getSettingValue("failed_auth_lock_time");
    }

    public function getSettingValueForTestName($name, $slug, $key)
    {
        $test = null;
        if ($name !== null) {
            $test = $this->testRepository->findRunnableByName($name);
        } else if ($slug !== null) {
            $test = $this->testRepository->findRunnableBySlug($slug);
        }
        return $this->getSettingValueForTest($test, $key);
    }

    public function getSettingValueForTest(Test $test, $key)
    {
        $value = $this->getSettingValue($key);

        if ($test !== null) {
            $valueTestOverride = $this->getTestConfigOverride($test->getConfigOverride(), $key);
            if ($valueTestOverride !== null) {
                return $valueTestOverride;
            }
        }

        return $value;
    }

    public function getSettingValueForSessionHash($hash, $key)
    {
        $session = $this->testSessionRepository->findOneBy(array("hash" => $hash));
        return $this->getSettingValueForSession($session, $key);
    }

    public function getSettingValueForSession($session, $key)
    {
        $value = $this->getSettingValue($key);

        if ($session !== null) {
            $test = $session->getTest();
            if ($test !== null) {
                $configOverride = $this->getTestConfigOverride($test->getConfigOverride(), $key);
                if ($configOverride !== null) {
                    return $configOverride;
                }
            }
        }

        return $value;
    }

    private function getTestConfigOverride($configString, $property)
    {
        $config = json_decode($configString, true);
        if ($config) {
            if (isset($config[$property])) {
                return $config[$property];
            }
        }
        return null;
    }

    public function getLastMessageFetchTime()
    {
        $time = $this->getSettingValue("last_message_fetch_time");
        if ($time !== null)
            return (int)$time;
        return null;
    }

    public function getSessionLimit()
    {
        $limit = $this->getSettingValue("session_limit");
        return (int)$limit;
    }

    public function getLocalSessionLimit()
    {
        $limit = $this->getSettingValue("local_session_limit");
        return (int)$limit;
    }

    private static function isPlatformVersionNewer($base_v, $compared_v)
    {
        $bvs = explode(".", $base_v);
        $cvs = explode(".", $compared_v);
        for ($i = 0; $i < count($bvs) && $i < count($cvs); $i++) {
            if ((is_numeric($bvs[$i]) && is_numeric($cvs[$i]) && $bvs[$i] > $cvs[$i]) || (!is_numeric($cvs[$i]) && is_numeric($bvs[$i])))
                return false;
            if ((is_numeric($bvs[$i]) && is_numeric($cvs[$i]) && $cvs[$i] > $bvs[$i]) || (!is_numeric($bvs[$i]) && is_numeric($cvs[$i])))
                return true;
        }
        return false;
    }

    public function setSettings($map, $exposed)
    {
        foreach ($map as $k => $v) {
            if (strpos($k, "_overridable") !== false)
                continue;
            if (isset($this->configSettings[$k . "_overridable"]) && $this->configSettings[$k . "_overridable"] === "false")
                continue;
            $setting = $this->settingsRepository->findKey($k);
            if ($setting) {
                $setting->setValue($v);
                $this->settingsRepository->save($setting);
            } else {
                $setting = new AdministrationSetting();
                $setting->setKey($k);
                $setting->setValue($v);
                $setting->setExposed($exposed);
                $this->settingsRepository->save($setting);
            }
        }
    }

    public function getTasksCollection()
    {
        return $this->scheduledTaskRepository->findAll();
    }

    public function isTaskScheduled()
    {
        $pending = $this->scheduledTaskRepository->findAllPending();
        if (count($pending) > 0)
            return true;

        $ongoing = $this->scheduledTaskRepository->findAllOngoing();
        if (count($ongoing) > 0) {
            return true;
        }
        return false;
    }

    public function scheduleTaskPackageInstall($installOptions, &$output = null, &$errors = null)
    {
        if ($this->isTaskScheduled()) {
            $errors[] = "tasks.already_scheduled";
            return false;
        }

        $app = new Application($this->kernel);
        $app->setAutoExit(false);
        $in = new ArrayInput(array(
            "command" => "concerto:task:package:install",
            "--method" => $installOptions["method"],
            "--name" => $installOptions["name"],
            "--mirror" => $installOptions["mirror"],
            "--url" => $installOptions["url"]
        ));
        $out = new BufferedOutput();
        $returnCode = $app->run($in, $out);
        $output = $out->fetch();
        return $returnCode === 0;
    }

    public function getApiClientsCollection()
    {
        return $this->apiClientRepository->findAll();
    }

    public function deleteApiClient($object_ids)
    {
        $object_ids = explode(",", $object_ids);
        $this->apiClientRepository->deleteById($object_ids);
    }

    public function clearApiClients()
    {
        $this->apiClientRepository->deleteAll();
    }

    public function addApiClient()
    {
        $app = new Application($this->kernel);
        $app->setAutoExit(false);
        $in = new ArrayInput(array(
            "command" => "oauth-server:client:create",
            "--grant-type" => array("token", "client_credentials")
        ));
        $out = new BufferedOutput();
        $return_code = $app->run($in, $out);
        $output = $out->fetch();
        return $return_code;
    }

    public function packageStatus(&$output)
    {
        if (self::getOS() == self::OS_WIN) {
            $output = "Windows OS is not supported by this command!";
            return false;
        }

        $rscript_path = $this->testRunnerSettings["rscript_exec"];
        $lib_loc = $this->getSettingValue("r_lib_path");
        $lib_loc_arg = $lib_loc ? "lib.loc='$lib_loc'" : "";

        $cmd = "$rscript_path -e \"installed.packages($lib_loc_arg)\"";

        $proc = new Process($cmd);
        $return_var = $proc->run();
        $output = $proc->getOutput();
        return $return_var === 0;
    }

    public function getContentTransferOptions()
    {
        return $this->getSettingValue("content_transfer_options");
    }

    public function getContentUrl()
    {
        return $this->getSettingValue("content_url");
    }

    public function exportContent($instructions = null, &$zipPath = null, &$output = null)
    {
        if ($instructions === null) $instructions = $this->getContentTranferOptions();
        $exportPath = realpath(__DIR__ . "/../Resources/export");
        $uniquePath = $exportPath . "/export_" . uniqid();

        $fs = new Filesystem();
        try {
            $fs->mkdir($uniquePath);
        } catch (IOException $ex) {
            return 1;
        }
        $zipPath = $uniquePath . "/export.concerto.zip";

        $app = new Application($this->kernel);
        $app->setAutoExit(false);
        $in = new ArrayInput(array(
            "command" => "concerto:content:export",
            "output" => $uniquePath,
            "--sc" => true,
            "--yes" => true,
            "--zip" => $zipPath,
            "--instructions" => $instructions
        ));
        $out = new BufferedOutput();
        $returnCode = $app->run($in, $out);
        $output = $out->fetch();
        return $returnCode;
    }

    public function updateLastImportTime()
    {
        $this->setSettings(array(
            "last_import_time" => date("Y-m-d H:i:s")
        ), false);
    }

    public function updateLastGitUpdateTime()
    {
        $this->setSettings(array(
            "last_git_update_time" => date("Y-m-d H:i:s")
        ), false);
    }

    public function setContentBlock($enabled)
    {
        $this->setSettings(array(
            "content_block" => $enabled ? 1 : 0
        ), false);
    }

    public function isContentBlocked()
    {
        return $this->getSettingValue("content_block") == 1;
    }

    /**
     * @return User
     */
    public function getAuthorizedUser()
    {
        return $this->securityTokenStorage->getToken()->getUser();
    }

    public function getSessionRunnerService()
    {
        return $this->getSettingValue("session_runner_service");
    }

    public function canDoMassContentModifications()
    {
        if (count($this->dataTableRepository->findDirectlyLocked()) > 0) return false;
        if (count($this->testRepository->findDirectlyLocked()) > 0) return false;
        if (count($this->testWizardRepository->findDirectlyLocked()) > 0) return false;
        if (count($this->viewTemplateRepository->findDirectlyLocked()) > 0) return false;
        return true;
    }

    public function getHomeTest()
    {
        $test_id = $this->getSettingValue("home_test_id");
        return $this->testRepository->find($test_id);
    }
}
