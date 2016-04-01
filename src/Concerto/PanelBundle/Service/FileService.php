<?php

namespace Concerto\PanelBundle\Service;

class FileService {

    private $environment;

    public function __construct($environment) {
        $this->environment = $environment;
    }

    protected function getUploadDirectory() {
        return dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . ($this->environment === "test" ? ("Tests" . DIRECTORY_SEPARATOR) : "") . "Resources" . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "files" . DIRECTORY_SEPARATOR;
    }

    public function moveUploadedFile($tmp_file, $file_name) {
        $upload_path = $this->getUploadDirectory() . $file_name;
        return move_uploaded_file($tmp_file, $upload_path);
    }

    public function listUploadedFiles($url_prefix) {
        $uris = array();

        try {
            foreach (new \DirectoryIterator($this->getUploadDirectory()) as $file_info) {
                if (!$file_info->isDot() && !$file_info->isDir() && substr($file_info->getFilename(), 0, 1) !== ".") {
                    $uris[] = array('url' => $url_prefix . $file_info->getFilename(), 'name' => $file_info->getFilename());
                }
            }
        } catch (\UnexpectedValueException $exc) {
            return false;
        }

        return $uris;
    }

    public function deleteUploadedFile($filename) {
        $uris = array();

        try {
            foreach (new \DirectoryIterator($this->getUploadDirectory()) as $file_info)
                if ((!$file_info->isDot() ) && ( $file_info->getFilename() == $filename ))
                    return (bool) unlink($file_info->getPathname());
        } catch (\UnexpectedValueException $exc) {
            // some logging would be nice
            return false;
        }
        return false;
    }

}
