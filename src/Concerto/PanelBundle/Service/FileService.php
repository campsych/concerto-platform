<?php

namespace Concerto\PanelBundle\Service;

use Symfony\Component\Asset\Packages;

class FileService
{
    private $environment;
    private $assetManager;

    const DIR_PRIVATE = 0;
    const DIR_PUBLIC = 1;
    const DIR_SESSION = 2;

    public function __construct($environment, Packages $assetManager)
    {
        $this->environment = $environment;
        $this->assetManager = $assetManager;
    }

    public function listFiles($path)
    {
        $urlPath = $this->canonicalizePath($path);
        $fullPath = realpath($this->getPublicUploadDirectory()) . "/" . $urlPath;
        $files = array_values(array_filter(
            scandir($fullPath),
            function ($path) {
                return !($path === '.' || $path === '..');
            }
        ));
        $files = array_map(function ($file) use ($fullPath, $urlPath) {
            $fullFilePath = $fullPath . "/" . $file;
            $date = new \DateTime('@' . filemtime($fullFilePath));
            return [
                'name' => basename($fullFilePath),
                'rights' => $this->parsePerms(fileperms($fullFilePath)),
                'size' => filesize($fullFilePath),
                'date' => $date->format('Y-m-d H:i:s'),
                'type' => is_dir($fullFilePath) ? 'dir' : 'file',
                'url' => $this->assetManager->getUrl("files/" . $urlPath . "/" . basename($fullFilePath))
            ];
        }, $files);
        return $files;
    }

    public function moveUploadedFile($tmpFile, $dirType, $file_name, &$error)
    {
        $uploadDir = realpath($dirType == self::DIR_PRIVATE ? $this->getPrivateUploadDirectory() : $this->getPublicUploadDirectory());
        $uploadPath = $uploadDir . "/" . $file_name;
        if (!is_writable($uploadDir)) {
            $error = $uploadDir . " is not writable";
            return false;
        }
        return move_uploaded_file($tmpFile, $uploadPath);
    }

    public function uploadFiles($dirType, $destination, $files, &$error, $sessionHash = null)
    {
        $path = null;
        switch ($dirType) {
            case self::DIR_PUBLIC:
                $path = $this->getPublicUploadDirectory();
                break;
            case self::DIR_SESSION:
                $path = $this->getSessionUploadDirectory($sessionHash);
                break;
            case self::DIR_PRIVATE:
            default:
                $path = $this->getPrivateUploadDirectory();
                break;
        }
        $dirPath = realpath($path) . "/" . $this->canonicalizePath($destination);
        foreach ($files as $file) {
            $uploaded = false;
            $fileName = $this->canonicalizePath(basename($file->getClientOriginalName()));
            if ($fileName) {
                $uploaded = move_uploaded_file(
                    $file->getRealPath(),
                    $dirPath . "/" . $fileName
                );
            }
            if ($uploaded === false) {
                $error = "upload_failed";
                return false;
            }
        }
        return true;
    }

    public function renameFile($item, $newItemPath, &$error)
    {
        $srcPath = realpath($this->getPublicUploadDirectory()) . "/" . $this->canonicalizePath($item);
        $dstPath = realpath($this->getPublicUploadDirectory()) . "/" . $this->canonicalizePath($newItemPath);
        if (!file_exists($srcPath)) {
            $error = 'file_not_found';
            return false;
        }
        $success = rename($srcPath, $dstPath);
        if (!$success) {
            $error = "renaming_failed";
            return false;
        }
        return true;
    }

    public function copyFiles($items, $newPath, $singleDstFileName, &$error)
    {
        foreach ($items as $oldPath) {
            $srcPath = $this->canonicalizePath($oldPath);
            $dstFileName = $this->canonicalizePath(basename($srcPath));
            if ($singleDstFileName) {
                $singleDstFileName = $this->canonicalizePath(basename($singleDstFileName));
                $dstFileName = $singleDstFileName;
            }
            $srcPath = realpath($this->getPublicUploadDirectory()) . "/" . $srcPath;
            $dstPath = realpath($this->getPublicUploadDirectory()) . "/" . $this->canonicalizePath($newPath) . "/" . $dstFileName;
            if (!file_exists($srcPath)) {
                $error = "copying_failed $srcPath";
                return false;
            }
            $copied = copy($srcPath, $dstPath);
            if ($copied === false) {
                $error = "copying_failed $srcPath -> $dstPath";
                return false;
            }
        }
        return true;
    }

    public function moveFiles($items, $newPath, &$error)
    {
        foreach ($items as $oldPath) {
            $srcPath = realpath($this->getPublicUploadDirectory()) . "/" . $this->canonicalizePath($oldPath);
            $dstDir = realpath($this->getPublicUploadDirectory()) . "/" . $this->canonicalizePath($newPath);
            $fileName = $this->canonicalizePath(basename($srcPath));
            $dstPath = $dstDir . "/" . $fileName;
            if (!file_exists($srcPath)) {
                $error = "moving_failed";
                return false;
            }
            $renamed = rename($srcPath, $dstPath);
            if ($renamed === false) {
                $error = "moving_failed";
                return false;
            }
        }
        return true;
    }

    public function deleteFiles($items, &$error)
    {
        foreach ($items as $path) {
            $path = realpath($this->getPublicUploadDirectory()) . "/" . $this->canonicalizePath($path);
            if (is_dir($path)) {
                $dirEmpty = (new \FilesystemIterator($path))->valid();
                if ($dirEmpty) {
                    $error = 'removing_failed_directory_not_empty';
                    return false;
                } else {
                    $removed = rmdir($path);
                }
            } else {
                $removed = unlink($path);
            }
            if ($removed === false) {
                $error = "removing_failed";
                return false;
            }
        }
        return true;
    }

    public function editFile($item, $content, &$error)
    {
        $path = realpath($this->getPublicUploadDirectory()) . "/" . $this->canonicalizePath($item);
        $success = file_put_contents($path, $content);
        if (!$success) {
            $error = "saving_failed";
            return false;
        }
        return true;
    }

    public function getFileContent($item, &$error)
    {
        $path = realpath($this->getPublicUploadDirectory()) . "/" . $this->canonicalizePath($item);
        if (!file_exists($path)) {
            $error = "file_not_found";
            return false;
        }
        $content = file_get_contents($path);
        if ($content === false) {
            $error = "file_not_found";
            return false;
        }
        return $content;
    }

    public function createDirectory($newPath, &$error)
    {
        $path = realpath($this->getPublicUploadDirectory()) . "/" . $this->canonicalizePath($newPath);
        if (file_exists($path) && is_dir($path)) {
            $error = 'folder_already_exists';
            return false;
        }
        $success = mkdir($path);
        if (!$success) {
            $error = "folder_creation_failed";
            return false;
        }
        return true;
    }

    public function createTempArchive($items, &$archivePath, &$error)
    {
        $archivePath = tempnam(sys_get_temp_dir(), 'archive');
        $zip = new \ZipArchive();
        if ($zip->open($archivePath, \ZipArchive::CREATE) !== true) {
            unlink($archivePath);
            $error = "file_not_found";
            return false;
        }
        foreach ($items as $path) {
            $zip->addFile($this->getPublicUploadDirectory() . $path, basename($path));
        }
        $zip->close();
        return true;
    }

    public function compressFiles($items, $destination, $compressedFilename, &$error)
    {
        $archivePath = realpath($this->getPublicUploadDirectory()) . "/" . $this->canonicalizePath($destination) . "/" . $this->canonicalizePath(basename($compressedFilename));
        $zip = new \ZipArchive();
        if ($zip->open($archivePath, \ZipArchive::CREATE) !== true) {
            $error = "compression_failed";
            return false;
        }
        foreach ($items as $path) {
            $fullPath = realpath($this->getPublicUploadDirectory()) . "/" . $this->canonicalizePath($path);
            if (is_dir($fullPath)) {
                $dirs = [
                    [
                        'dir' => basename($path),
                        'path' => $fullPath,
                    ]
                ];
                while (count($dirs)) {
                    $dir = current($dirs);
                    $zip->addEmptyDir($dir['dir']);
                    $dh = opendir($dir['path']);
                    while ($file = readdir($dh)) {
                        if ($file != '.' && $file != '..') {
                            $filePath = $dir['path'] . '/' . $file;
                            if (is_file($filePath)) {
                                $zip->addFile(
                                    $dir['path'] . '/' . $file,
                                    $dir['dir'] . '/' . basename($file)
                                );
                            } elseif (is_dir($filePath)) {
                                $dirs[] = [
                                    'dir' => $dir['dir'] . '/' . $file,
                                    'path' => $dir['path'] . '/' . $file
                                ];
                            }
                        }
                    }
                    closedir($dh);
                    array_shift($dirs);
                }
            } else {
                $zip->addFile($fullPath, basename($fullPath));
            }
        }
        $success = $zip->close();
        if (!$success) {
            $error = "compression_failed";
            return false;
        }
        return true;
    }

    public function extractFiles($destination, $item, $folderName, &$error)
    {
        $archivePath = realpath($this->getPublicUploadDirectory()) . "/" . $this->canonicalizePath($item);
        $folderPath = realpath($this->getPublicUploadDirectory()) . "/" . $this->canonicalizePath($destination) . "/" . $this->canonicalizePath(basename($folderName));
        $zip = new \ZipArchive;
        if ($zip->open($archivePath) === false) {
            $error = 'archive_opening_failed';
            return false;
        }
        mkdir($folderPath);
        $zip->extractTo($folderPath);
        $success = $zip->close();
        if (!$success) {
            $error = "extraction_failed";
            return false;
        }
        return true;
    }

    public function setPermissions($items, $perms, $recursive, &$error)
    {
        foreach ($items as $path) {
            $path = realpath($this->getPublicUploadDirectory()) . "/" . $this->canonicalizePath($path);
            if (!file_exists($path)) {
                $error = 'file_not_found';
                return false;
            }
            if (is_dir($path) && $recursive === true) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($path),
                    RecursiveIteratorIterator::SELF_FIRST
                );
                foreach ($iterator as $item) {
                    $itemPath = realpath($this->getPublicUploadDirectory()) . "/" . $item;
                    $changed = chmod($itemPath, octdec($perms));

                    if ($changed === false) {
                        $error = "permissions_change_failed";
                        return false;
                    }
                }
            }
            $success = chmod($path, octdec($perms));
            if (!$success) {
                $error = "permissions_change_failed";
                return false;
            }
            return true;
        }
    }

    public function getPrivateUploadDirectory()
    {
        return dirname(__FILE__) . "/../" . ($this->environment === "test" ? ("../../../tests/") : "") . "Resources/import/";
    }

    public function getPublicUploadDirectory()
    {
        return dirname(__FILE__) . "/../" . ($this->environment === "test" ? ("../../../tests/") : "") . "Resources/public/files/";
    }

    public function getSessionUploadDirectory($hash)
    {
        return dirname(__FILE__) . "/../../TestBundle/" . ($this->environment === "test" ? ("../../../tests/") : "") . "Resources/sessions/$hash/files/";
    }

    private function parsePerms($perms)
    {
        if (($perms & 0xC000) == 0xC000) {
            // Socket
            $info = 's';
        } elseif (($perms & 0xA000) == 0xA000) {
            // Symbolic Link
            $info = 'l';
        } elseif (($perms & 0x8000) == 0x8000) {
            // Regular
            $info = '-';
        } elseif (($perms & 0x6000) == 0x6000) {
            // Block special
            $info = 'b';
        } elseif (($perms & 0x4000) == 0x4000) {
            // Directory
            $info = 'd';
        } elseif (($perms & 0x2000) == 0x2000) {
            // Character special
            $info = 'c';
        } elseif (($perms & 0x1000) == 0x1000) {
            // FIFO pipe
            $info = 'p';
        } else {
            // Unknown
            $info = 'u';
        }
        // Owner
        $info .= (($perms & 0x0100) ? 'r' : '-');
        $info .= (($perms & 0x0080) ? 'w' : '-');
        $info .= (($perms & 0x0040) ?
            (($perms & 0x0800) ? 's' : 'x') :
            (($perms & 0x0800) ? 'S' : '-'));
        // Group
        $info .= (($perms & 0x0020) ? 'r' : '-');
        $info .= (($perms & 0x0010) ? 'w' : '-');
        $info .= (($perms & 0x0008) ?
            (($perms & 0x0400) ? 's' : 'x') :
            (($perms & 0x0400) ? 'S' : '-'));
        // World
        $info .= (($perms & 0x0004) ? 'r' : '-');
        $info .= (($perms & 0x0002) ? 'w' : '-');
        $info .= (($perms & 0x0001) ?
            (($perms & 0x0200) ? 't' : 'x') :
            (($perms & 0x0200) ? 'T' : '-'));
        return $info;
    }

    public function canonicalizePath($path)
    {
        $dirSep = DIRECTORY_SEPARATOR;
        $wrongDirSep = DIRECTORY_SEPARATOR === '/' ? '\\' : '/';
        // Replace incorrect dir separators
        $path = str_replace($wrongDirSep, $dirSep, $path);
        $path = explode($dirSep, $path);
        $stack = array();
        foreach ($path as $seg) {
            if ($seg == '..') {
                // Ignore this segment, remove last segment from stack
                array_pop($stack);
                continue;
            }
            if ($seg == '.') {
                // Ignore this segment
                continue;
            }
            $stack[] = $seg;
        }
        // Remove last /
        if (empty($stack[count($stack) - 1])) {
            array_pop($stack);
        }
        return implode($dirSep, $stack);
    }
}
