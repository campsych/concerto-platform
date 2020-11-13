<?php

namespace Concerto\PanelBundle\Service;

use Symfony\Component\Filesystem\Filesystem;

class MaintenanceService
{
    private $expirationTime;

    public function __construct($administration)
    {
        $this->expirationTime = (int)$administration["internal"]["session_files_expiration"];
    }

    private function getSessionsPath()
    {
        return realpath(dirname(__FILE__) . '/../../TestBundle/Resources/sessions');
    }

    private function getLogsPath()
    {
        return realpath(dirname(__FILE__) . '/../../../../var/logs');
    }

    public function deleteOldSessions()
    {
        if ($this->expirationTime < 0) return;

        $borderTime = time() - ($this->expirationTime * 86400);
        $fs = new Filesystem();
        foreach (new \DirectoryIterator($this->getSessionsPath()) as $sessionDir) {
            if ($sessionDir->isDir() && !$sessionDir->isDot()) {
                if ($sessionDir->getMTime() < $borderTime && $fs->exists($sessionDir->getRealPath())) {
                    @$fs->remove($sessionDir->getRealPath());
                }
            }
        }

        foreach (new \DirectoryIterator($this->getLogsPath()) as $file) {
            if (in_array($file->getFilename(), [
                "dev.log",
                "prod.log",
                "forker.log"
            ])) continue;
            if ($file->isFile() && $file->getMTime() < $borderTime) {
                @$fs->remove($file->getRealPath());
            }
        }
    }
}