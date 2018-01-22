<?php

namespace Concerto\PanelBundle\Service;

class FileService
{
    private $environment;

    const DIR_PRIVATE = 0;
    const DIR_PUBLIC = 1;

    public function __construct($environment)
    {
        $this->environment = $environment;
    }

    public function getPrivateUploadDirectory()
    {
        return dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . ($this->environment === "test" ? (".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "tests" . DIRECTORY_SEPARATOR) : "") . "Resources" . DIRECTORY_SEPARATOR . "import" . DIRECTORY_SEPARATOR;
    }

    public function getPublicUploadDirectory()
    {
        return dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . ($this->environment === "test" ? (".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "tests" . DIRECTORY_SEPARATOR) : "") . "Resources" . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "files" . DIRECTORY_SEPARATOR;
    }

    public function moveUploadedFile($tmp_file, $dir_type, $file_name, &$message)
    {
        $upload_dir = realpath($dir_type == self::DIR_PRIVATE ? $this->getPrivateUploadDirectory() : $this->getPublicUploadDirectory());
        $upload_path = $upload_dir . DIRECTORY_SEPARATOR . $file_name;
        if (!is_writable($upload_dir)) {
            $message = $upload_dir . " is not writable";
            return false;
        }
        return move_uploaded_file($tmp_file, $upload_path);
    }

    public function listUploadedFiles($url_prefix)
    {
        $uris = array();

        try {
            foreach (new \DirectoryIterator($this->getPublicUploadDirectory()) as $file_info) {
                if (!$file_info->isDot() && !$file_info->isDir() && substr($file_info->getFilename(), 0, 1) !== ".") {
                    $uris[] = array('url' => $url_prefix . $file_info->getFilename(), 'name' => $file_info->getFilename());
                }
            }
        } catch (\UnexpectedValueException $exc) {
            return false;
        }

        return $uris;
    }

    public function deleteUploadedFile($filename)
    {
        $uris = array();

        try {
            foreach (new \DirectoryIterator($this->getPublicUploadDirectory()) as $file_info)
                if ((!$file_info->isDot()) && ($file_info->getFilename() == $filename))
                    return (bool)unlink($file_info->getPathname());
        } catch (\UnexpectedValueException $exc) {
            // some logging would be nice
            return false;
        }
        return false;
    }

}
