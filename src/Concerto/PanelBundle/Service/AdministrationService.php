<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Entity\AdministrationSetting;
use Concerto\PanelBundle\Entity\Message;
use Concerto\PanelBundle\Repository\AdministrationSettingRepository;
use Concerto\PanelBundle\Repository\MessageRepository;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Templating\EngineInterface;
use Concerto\PanelBundle\Entity\TestSession;
use Concerto\PanelBundle\Repository\TestSessionLogRepository;
use Concerto\PanelBundle\Entity\TestSessionLog;
use Symfony\Component\Yaml\Yaml;
use DateTime;

class AdministrationService {

    private $settingsRepository;
    private $messagesRepository;
    private $authorizationChecker;
    private $configSettings;
    private $templating;
    private $testSessionLogRepository;

    public function __construct(AdministrationSettingRepository $settingsRepository, MessageRepository $messageRepository, AuthorizationChecker $authorizationChecker, $configSettings, EngineInterface $templating, TestSessionLogRepository $testSessionLogRepository) {
        $this->settingsRepository = $settingsRepository;
        $this->messagesRepository = $messageRepository;
        $this->authorizationChecker = $authorizationChecker;
        $this->configSettings = $configSettings;
        $this->templating = $templating;
        $this->testSessionLogRepository = $testSessionLogRepository;
    }

    public function insertSessionLimitMessage(TestSession $session) {
        $msg = new Message();
        $msg->setCagegory(Message::CATEGORY_SYSTEM);
        $msg->setSubject("Session limit reached.");
        $content = $this->templating->render("ConcertoPanelBundle:Administration:msg_session_limit.html.twig", array(
            "limit" => $this->getSessionLimit()
        ));
        $msg->setMessage($content);
        $this->messagesRepository->save($msg);
    }

    private function fetchTestSessionLogs($start_time) {
        foreach ($this->testSessionLogRepository->findAllNewerThan($start_time) as $log) {
            $msg = new Message();
            $msg->setTime($log->getCreated());
            $msg->setCagegory(Message::CATEGORY_TEST);
            $error_source = "";
            switch ($log->getType()) {
                case TestSessionLog::TYPE_JS: $error_source = "JS";
                    break;
                case TestSessionLog::TYPE_R: $error_source = "R";
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

    private function fetchFeed($url, $start_time) {
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

    public function fetchMessagesCollection() {
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

        $this->setSettings(array("last_message_fetch_time" => $this_fetch_time));
    }

    public function getMessagesCollection() {
        $this->fetchMessagesCollection();
        return $this->messagesRepository->findAll();
    }

    public function deleteMessage($object_ids) {
        $object_ids = explode(",", $object_ids);
        $this->messagesRepository->deleteById($object_ids);
    }

    public function clearMessages() {
        $this->messagesRepository->deleteAll();
    }

    public function getSettingsMap() {
        $map = $this->configSettings;
        foreach ($map as $k => $v) {
            $map[$k] = (string) $v;
        }
        foreach ($this->settingsRepository->findAll() as $setting) {
            if (array_key_exists($setting->getKey() . "_overridable", $this->configSettings) && $this->configSettings[$setting->getKey() . "_overridable"] === "0") {
                continue;
            }
            $map[$setting->getKey()] = $setting->getValue();
        }
        return $map;
    }

    public function getSettingValue($key) {
        $map = $this->getSettingsMap();
        if (array_key_exists($key, $map)) {
            return $map[$key];
        }
        return null;
    }

    public function isApiEnabled() {
        $enabled = $this->getSettingValue("api_enabled");
        return $enabled == "1";
    }

    public function getLastMessageFetchTime() {
        $time = $this->getSettingValue("last_message_fetch_time");
        if ($time !== null)
            return (int) $time;
        return null;
    }

    public function getSessionLimit() {
        $limit = $this->getSettingValue("session_limit");
        return (int) $limit;
    }

    public function getGlobalFeedUrl() {
        return $this->getSettingValue("global_feed");
    }

    public function getLocalFeedUrl() {
        return $this->getSettingValue("local_feed");
    }

    public function getInstalledContentVersion() {
        return $this->getSettingValue("installed_content_version");
    }

    public function setInstalledContentVersion($version) {
        $this->setSettings(array("installed_content_version" => $version));
    }

    public function getBackupPlatformVersion() {
        return $this->getSettingValue("backup_platform_version");
    }

    public function setBackupPlatformVersion($version) {
        $this->setSettings(array("backup_platform_version" => $version));
    }

    public function getBackupPlatformPath() {
        return $this->getSettingValue("backup_platform_path");
    }

    public function setBackupPlatformPath($path) {
        $this->setSettings(array("backup_platform_path" => $path));
    }

    public function getBackupDatabasePath() {
        return $this->getSettingValue("backup_db_path");
    }

    public function setBackupDatabasePath($path) {
        $this->setSettings(array("backup_db_path" => $path));
    }

    public function getBackupContentVersion() {
        return $this->getSettingValue("backup_content_version");
    }

    public function setBackupContentVersion($version) {
        $this->setSettings(array("backup_content_version" => $version));
    }

    public function getBackupTime() {
        return $this->getSettingValue("backup_time");
    }

    public function setBackupTime(DateTime $time) {
        $this->setSettings(array("backup_time" => $time->getTimestamp()));
    }

    public function setSettings($map) {
        foreach ($map as $k => $v) {
            if (strpos($k, "_overridable") !== false)
                continue;
            if (array_key_exists($k . "_overridable", $this->configSettings) && $this->configSettings[$k . "_overridable"] === "0")
                continue;
            $setting = $this->settingsRepository->findKey($k);
            if ($setting) {
                $setting->setValue($v);
                $setting->setUpdated();
                $this->settingsRepository->save($setting);
            } else {
                $setting = new AdministrationSetting();
                $setting->setKey($k);
                $setting->setValue($v);
                $this->settingsRepository->save($setting);
            }
        }
    }

}
