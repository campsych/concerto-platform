<?php

namespace Concerto\PanelBundle\Service;


use Symfony\Component\Filesystem\Filesystem;

class MaintenanceService
{
    private $expirationTime;

    public function __construct($administration)
    {
        $this->expirationTime = $administration["internal"]["session_files_expiration"];
    }

    private function getSessionsPath()
    {
        return realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . "TestBundle" . DIRECTORY_SEPARATOR . "Resources" . DIRECTORY_SEPARATOR . "sessions");
    }

    public function deleteOldSessions()
    {
        $borderTime = time() - ((int)$this->expirationTime * 86400);
        $fs = new Filesystem();
        foreach (new \DirectoryIterator($this->getSessionsPath()) as $nodeDir) {
            if ($nodeDir->isDir() && !$nodeDir->isDot()) {
                foreach (new \DirectoryIterator($nodeDir->getRealPath()) as $sessionDir) {
                    if ($sessionDir->isDir() && !$sessionDir->isDot()) {
                        if ($sessionDir->getMTime() > $borderTime && $fs->exists($sessionDir->getRealPath())) {
                            @$fs->remove($sessionDir->getRealPath());
                        }
                    }
                }
            }
        }
    }
}