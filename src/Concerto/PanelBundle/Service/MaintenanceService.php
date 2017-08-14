<?php

namespace Concerto\PanelBundle\Service;


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
        foreach (new \DirectoryIterator($this->getSessionsPath()) as $nodeDir) {
            if ($nodeDir->isDir() && !$nodeDir->isDot()) {
                foreach (new \DirectoryIterator($nodeDir->getRealPath()) as $sessionDir) {
                    if ($sessionDir->isDir() && !$sessionDir->isDot()) {
                        $expired = true;
                        foreach (new \DirectoryIterator($sessionDir->getRealPath()) as $fileInfo) {
                            if ($fileInfo->isFile() && $fileInfo->getMTime() > $borderTime) {
                                $expired = false;
                                break;
                            }
                        }
                        if($expired) {
                            foreach (new \DirectoryIterator($sessionDir->getRealPath()) as $fileInfo) {
                                if ($fileInfo->isFile()) {
                                    unlink($fileInfo->getRealPath());
                                }
                            }
                            rmdir($sessionDir->getRealPath());
                        }
                    }
                }
            }
        }
    }
}