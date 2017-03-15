<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Entity\AdministrationSetting;
use Concerto\PanelBundle\Entity\Message;
use Concerto\PanelBundle\Repository\AdministrationSettingRepository;
use Concerto\PanelBundle\Repository\MessageRepository;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class AdministrationService {

    private $settingsRepository;
    private $messagesRepository;
    private $authorizationChecker;
    private $configSettings;

    public function __construct(AdministrationSettingRepository $settingsRepository, MessageRepository $messageRepository, AuthorizationChecker $authorizationChecker, $configSettings) {
        $this->settingsRepository = $settingsRepository;
        $this->messagesRepository = $messageRepository;
        $this->authorizationChecker = $authorizationChecker;
        $this->configSettings = $configSettings;
    }

    public function fetchMessagesCollection() {
        //@TODO
    }

    public function getMessagesCollection() {
        $this->fetchMessagesCollection();
        return $this->messagesRepository->findAll();
    }

    public function getSettingsMap() {
        $map = $this->configSettings;
        foreach ($map as $k => $v) {
            $map[$k] = (string) $v;
        }
        foreach ($this->settingsRepository->findAll() as $setting) {
            if (array_key_exists($setting->getKey() . "_overridable", $this->configSettings) && $this->configSettings[$setting->getKey() . "_overridable"] === "1") {
                $map[$setting->getKey()] = $setting->getValue();
            }
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

    public function getSessionLimit() {
        $limit = $this->getSettingValue("session_limit");
        return (int) $limit;
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
