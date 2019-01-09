<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Entity\AdministrationSetting;
use Concerto\PanelBundle\Entity\Message;
use Concerto\PanelBundle\Repository\AdministrationSettingRepository;
use Concerto\PanelBundle\Repository\MessageRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
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

    private $settingsRepository;
    private $messagesRepository;
    private $authorizationChecker;
    private $configSettings;
    private $templating;
    private $testSessionLogRepository;
    private $doctrine;
    private $scheduledTaskRepository;
    private $rootDir;
    private $kernel;
    private $apiClientRepository;
    private $testRunnerSettings;

    public function __construct(AdministrationSettingRepository $settingsRepository, MessageRepository $messageRepository, AuthorizationCheckerInterface $authorizationChecker, $configSettings, $version, $rootDir, EngineInterface $templating, TestSessionLogRepository $testSessionLogRepository, RegistryInterface $doctrine, ScheduledTaskRepository $scheduledTaskRepository, KernelInterface $kernel, ClientRepository $clientRepository, $testRunnerSettings)
    {
        $this->settingsRepository = $settingsRepository;
        $this->messagesRepository = $messageRepository;
        $this->authorizationChecker = $authorizationChecker;
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
        foreach ($this->testSessionLogRepository->findAllNewerThan($start_time) as $log) {
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
        $global_feed_url = $this->getGlobalFeedUrl();
        if ($global_feed_url)
            $this->fetchFeed($global_feed_url, $last_fetch_time);
        $local_feed_url = $this->getLocalFeedUrl();
        if ($local_feed_url)
            $this->fetchFeed($local_feed_url, $last_fetch_time);

        $this->setSettings(array("last_message_fetch_time" => $this_fetch_time), false);
    }

    public function getMessagesCollection()
    {
        $this->fetchMessagesCollection();
        return $this->messagesRepository->findAll();
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
            if (array_key_exists($setting->getKey() . "_overridable", $map) && $map[$setting->getKey() . "_overridable"] === "0") {
                continue;
            }
            $map[$setting->getKey()] = $setting->getValue();
        }
        return $map;
    }

    public function getInternalSettingsMap($full = false)
    {
        $map = $this->configSettings["internal"];
        foreach ($map as $k => $v) {
            $map[$k] = (string)$v;
        }
        if ($full) {
            $map["available_content_version"] = $this->getAvailableContentVersion();
            $map["available_platform_version"] = $this->getAvailablePlatformVersion();
            $map["incremental_content_changelog"] = $this->getIncrementalContentChangelog();
            $map["incremental_platform_changelog"] = $this->getIncrementalPlatformChangelog();
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
        $map = $this->getAllSettingsMap();
        if (array_key_exists($key, $map)) {
            return $map[$key];
        }
        return null;
    }

    public function isApiEnabled()
    {
        $enabled = $this->getSettingValue("api_enabled");
        return $enabled == "1";
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

    public function getGlobalFeedUrl()
    {
        return $this->getSettingValue("global_feed");
    }

    public function getLatestPlatformMeta()
    {
        return "https://raw.githubusercontent.com/campsych/concerto-platform/" . $this->getGitBranch() . "/src/Concerto/PanelBundle/Resources/public/feeds/platform_meta.yml";
    }

    public function getGitBranch()
    {
        return $this->getSettingValue("git_branch");
    }

    public function getLocalFeedUrl()
    {
        return $this->getSettingValue("local_feed");
    }

    public function getInstalledContentVersion()
    {
        return $this->getSettingValue("installed_content_version");
    }

    public function getInstalledPlatformVersion()
    {
        return $this->configSettings["internal"]["version"];
    }

    public function setInstalledContentVersion($version)
    {
        $this->setSettings(array("installed_content_version" => $version), false);
    }

    public function getBackupPlatformVersion()
    {
        return $this->getSettingValue("backup_platform_version");
    }

    public function setBackupPlatformVersion($version)
    {
        $this->setSettings(array("backup_platform_version" => $version), false);
    }

    public function getBackupPlatformPath()
    {
        return $this->getSettingValue("backup_platform_path");
    }

    public function setBackupPlatformPath($path)
    {
        $this->setSettings(array("backup_platform_path" => $path), false);
    }

    public function getBackupDatabasePath()
    {
        return $this->getSettingValue("backup_db_path");
    }

    public function setBackupDatabasePath($path)
    {
        $this->setSettings(array("backup_db_path" => $path), false);
    }

    public function getBackupContentVersion()
    {
        return $this->getSettingValue("backup_content_version");
    }

    public function setBackupContentVersion($version)
    {
        $this->setSettings(array("backup_content_version" => $version), false);
    }

    public function getBackupTime()
    {
        return $this->getSettingValue("backup_time");
    }

    public function setBackupTime(DateTime $time)
    {
        $this->setSettings(array("backup_time" => $time->getTimestamp()), false);
    }

    public function getAvailableContentVersion()
    {
        $url = realpath($this->rootDir . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR . "Concerto" . DIRECTORY_SEPARATOR . "PanelBundle" . DIRECTORY_SEPARATOR . "Resources" . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "feeds") . DIRECTORY_SEPARATOR . "content_meta.yml";
        $raw_feed = file_get_contents($url);
        $feed = Yaml::parse($raw_feed);
        return $feed["version"];
    }

    public function getIncrementalContentChangelog()
    {
        $changelog = array();

        $url = realpath($this->rootDir . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR . "Concerto" . DIRECTORY_SEPARATOR . "PanelBundle" . DIRECTORY_SEPARATOR . "Resources" . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "feeds") . DIRECTORY_SEPARATOR . "content_meta.yml";
        $raw_feed = file_get_contents($url);
        $feed = Yaml::parse($raw_feed);

        foreach ($feed["changelog"] as $version) {
            if (self::isContentVersionNewer($this->getInstalledContentVersion(), $version["version"])) {
                array_push($changelog, $version);
            } else {
                break;
            }
        }
        return $changelog;
    }

    public function getIncrementalPlatformChangelog()
    {
        if (!$this->isOnline())
            return null;

        $url = $this->getLatestPlatformMeta();
        $raw_feed = file_get_contents($url);
        $feed = Yaml::parse($raw_feed);

        $changelog = array();
        foreach ($feed["changelog"] as $version) {
            if (self::isPlatformVersionNewer($this->getInstalledPlatformVersion(), $version["version"])) {
                array_push($changelog, $version);
            } else {
                break;
            }
        }
        return $changelog;
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

    private static function isContentVersionNewer($base_v, $compared_v)
    {
        $cvs = explode(".", $compared_v);
        $bvs = explode(".", $base_v);

        for ($i = 0; $i < count($bvs) && $i < count($cvs); $i++) {
            if ($bvs[$i] > $cvs[$i])
                return false;
            if ($cvs[$i] > $bvs[$i])
                return true;
        }
        return false;
    }

    public function getAvailablePlatformVersion()
    {
        if (!$this->isOnline())
            return null;

        $url = $this->getLatestPlatformMeta();
        $raw_feed = file_get_contents($url);
        $feed = Yaml::parse($raw_feed);
        return $feed["version"];
    }

    public function isOnline()
    {
        return $this->getSettingValue("online");
    }

    public function isUpdatePossible(&$error_message)
    {
        //check if not Windows OS
        if (strpos(strtolower(PHP_OS), "win") !== false) {
            $error_message = "Windows OS is not supported by this command!";
            return false;
        }

        //check if MySQL database driver
        $upgrade_connection = $this->getSettingValue("upgrade_connection");
        $connection = $this->doctrine->getConnection($upgrade_connection);
        if ($connection->getDriver()->getName() !== "pdo_mysql") {
            $error_message = "only MySQL database driver is supported by this command!";
            return false;
        }

        //check if zip exists
        $cmd = 'command -v zip >/dev/null 2>&1 || { exit 1; }';
        system($cmd, $return_var);
        if ($return_var !== 0) {
            $error_message = "no zip found!";
            return false;
        }

        //check if unzip exists
        $cmd = 'command -v unzip >/dev/null 2>&1 || { exit 1; }';
        system($cmd, $return_var);
        if ($return_var !== 0) {
            $error_message = "no unzip found!";
            return false;
        }

        return true;
    }

    public function setSettings($map, $exposed)
    {
        foreach ($map as $k => $v) {
            if (strpos($k, "_overridable") !== false)
                continue;
            if (array_key_exists($k . "_overridable", $this->configSettings) && $this->configSettings[$k . "_overridable"] === "0")
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

    public function scheduleRestoreTask(&$output, $busy_check)
    {
        if ($busy_check) {
            $pending = $this->scheduledTaskRepository->findAllPending();
            if (count($pending) > 0)
                return -1;

            $ongoing = $this->scheduledTaskRepository->findAllOngoing();
            if (count($ongoing) > 0) {
                return -1;
            }
        }

        $app = new Application($this->kernel);
        $app->setAutoExit(false);
        $in = new ArrayInput(array(
            "command" => "concerto:restore"
        ));
        $out = new BufferedOutput();
        $return_code = $app->run($in, $out);
        $output = $out->fetch();
        return $return_code;
    }

    public function scheduleBackupTask(&$output, $busy_check)
    {
        if ($busy_check) {
            $pending = $this->scheduledTaskRepository->findAllPending();
            if (count($pending) > 0)
                return -1;

            $ongoing = $this->scheduledTaskRepository->findAllOngoing();
            if (count($ongoing) > 0) {
                return -1;
            }
        }

        $app = new Application($this->kernel);
        $app->setAutoExit(false);
        $in = new ArrayInput(array(
            "command" => "concerto:backup"
        ));
        $out = new BufferedOutput();
        $return_code = $app->run($in, $out);
        $output = $out->fetch();
        return $return_code;
    }

    public function scheduleContentUpgradeTask(&$output, $backup, $busy_check)
    {
        if ($busy_check) {
            $pending = $this->scheduledTaskRepository->findAllPending();
            if (count($pending) > 0)
                return -1;

            $ongoing = $this->scheduledTaskRepository->findAllOngoing();
            if (count($ongoing) > 0) {
                return -1;
            }
        }

        $app = new Application($this->kernel);
        $app->setAutoExit(false);
        $options = array("command" => "concerto:content:upgrade");
        if ($backup === "true") $options["--backup"] = "true";
        $in = new ArrayInput($options);
        $out = new BufferedOutput();
        $return_code = $app->run($in, $out);
        $output = $out->fetch();
        return $return_code;
    }

    public function schedulePlatformUpgradeTask(&$output, $backup, $busy_check)
    {
        if ($busy_check) {
            $pending = $this->scheduledTaskRepository->findAllPending();
            if (count($pending) > 0)
                return -1;

            $ongoing = $this->scheduledTaskRepository->findAllOngoing();
            if (count($ongoing) > 0) {
                return -1;
            }
        }

        $app = new Application($this->kernel);
        $app->setAutoExit(false);
        $options = array("command" => "concerto:upgrade");
        if ($backup == "true") $options["--backup"] = "true";
        $in = new ArrayInput($options);
        $out = new BufferedOutput();
        $return_code = $app->run($in, $out);
        $output = $out->fetch();
        return $return_code;
    }

    public function schedulePackageInstallTask(&$output, $install_options, $busy_check)
    {
        if ($busy_check) {
            $pending = $this->scheduledTaskRepository->findAllPending();
            if (count($pending) > 0)
                return -1;

            $ongoing = $this->scheduledTaskRepository->findAllOngoing();
            if (count($ongoing) > 0) {
                return -1;
            }
        }

        $app = new Application($this->kernel);
        $app->setAutoExit(false);
        $in = new ArrayInput(array(
            "command" => "concerto:package:install",
            "--method" => $install_options["method"],
            "--name" => $install_options["name"],
            "--mirror" => $install_options["mirror"],
            "--url" => $install_options["url"]
        ));
        $out = new BufferedOutput();
        $return_code = $app->run($in, $out);
        $output = $out->fetch();
        return $return_code;
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
        //check if not Windows OS
        if (strpos(strtolower(PHP_OS), "win") !== false) {
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

}
