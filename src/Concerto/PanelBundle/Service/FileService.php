<?php

namespace Concerto\PanelBundle\Service;

use Symfony\Component\Asset\Packages;

class FileService
{
    private $environment;
    private $assetManager;

    const DIR_PRIVATE = 0;
    const DIR_PUBLIC = 1;

    public function __construct($environment, Packages $assetManager)
    {
        $this->environment = $environment;
        $this->assetManager = $assetManager;
    }

    public function listFiles($path)
    {
        $files = array_values(array_filter(
            scandir($this->getPublicUploadDirectory() . $path),
            function ($path) {
                return !($path === '.' || $path === '..');
            }
        ));
        $files = array_map(function ($file) use ($path) {
            $file = $this->canonicalizePath(
                $this->getPublicUploadDirectory() . $path . "/" . $file
            );
            $date = new \DateTime('@' . filemtime($file));
            return [
                'name' => basename($file),
                'rights' => $this->parsePerms(fileperms($file)),
                'size' => filesize($file),
                'date' => $date->format('Y-m-d H:i:s'),
                'type' => is_dir($file) ? 'dir' : 'file',
                'url' => $this->assetManager->getUrl("bundles/concertopanel/files/" . basename($file))
            ];
        }, $files);
        return $files;
    }

    public function uploadFiles($dirType, $destination, $files, &$error)
    {
        $basePath = realpath($dirType == self::DIR_PRIVATE ? $this->getPrivateUploadDirectory() : $this->getPublicUploadDirectory());
        $path = $this->canonicalizePath($basePath . $destination);
        foreach ($files as $file) {
            $fileInfo = pathinfo($file->getClientOriginalName());
            $fileName = $this->normalizeName($fileInfo['filename']) . '.' . $fileInfo['extension'];
            $uploaded = move_uploaded_file(
                $file->getRealPath(),
                $path . "/" . $fileName
            );
            if ($uploaded === false) {
                $error = "upload_failed";
                return false;
            }
        }
        return true;
    }

    public function renameFile($item, $newItemPath, &$error)
    {
        $oldPath = $this->getPublicUploadDirectory() . $item;
        $newPath = $this->getPublicUploadDirectory() . $newItemPath;
        if (!file_exists($oldPath)) {
            $errror = 'file_not_found';
            return false;
        }
        $success = rename($oldPath, $newPath);
        if (!$success) {
            $error = "renaming_failed";
            return false;
        }
        return true;
    }

    public function copyFiles($items, $newPath, &$error)
    {
        $newPath = $this->getPublicUploadDirectory() . $this->canonicalizePath($newPath) . DIRECTORY_SEPARATOR;
        foreach ($items as $oldPath) {
            if (!file_exists($this->getPublicUploadDirectory() . $oldPath)) {
                $error = "copying_failed";
                return false;
            }
            $copied = copy(
                $this->getPublicUploadDirectory() . $oldPath,
                $newPath . basename($oldPath)
            );
            if ($copied === false) {
                $error = "copying_failed";
                return false;
            }
        }
        return true;
    }

    public function moveFiles($items, $newPath, &$error)
    {
        $newPath = $this->getPublicUploadDirectory() . $this->canonicalizePath($newPath) . DIRECTORY_SEPARATOR;
        foreach ($items as $oldPath) {
            if (!file_exists($this->getPublicUploadDirectory() . $oldPath)) {
                $error = "moving_failed";
                return false;
            }
            $renamed = rename($this->getPublicUploadDirectory() . $oldPath, $newPath . basename($oldPath));
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
            $path = $this->canonicalizePath($this->getPublicUploadDirectory() . $path);
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
        $path = $this->getPublicUploadDirectory() . $item;
        $success = file_put_contents($path, $content);
        if (!$success) {
            $error = "saving_failed";
            return false;
        }
        return true;
    }

    public function getFileContent($item, &$error)
    {
        $path = $this->getPublicUploadDirectory() . $item;
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
        $path = $this->getPublicUploadDirectory() . $newPath;
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
        $archivePath = $this->getPublicUploadDirectory() . $destination . $compressedFilename;
        $zip = new \ZipArchive();
        if ($zip->open($archivePath, \ZipArchive::CREATE) !== true) {
            $error = "compression_failed";
            return false;
        }
        foreach ($items as $path) {
            $fullPath = $this->getPublicUploadDirectory() . $path;
            if (is_dir($fullPath)) {
                $dirs = [
                    [
                        'dir' => basename($path),
                        'path' => $this->canonicalizePath($this->getPublicUploadDirectory() . $path),
                    ]
                ];
                while (count($dirs)) {
                    $dir = current($dirs);
                    $zip->addEmptyDir($dir['dir']);
                    $dh = opendir($dir['path']);
                    while ($file = readdir($dh)) {
                        if ($file != '.' && $file != '..') {
                            $filePath = $dir['path'] . DIRECTORY_SEPARATOR . $file;
                            if (is_file($filePath)) {
                                $zip->addFile(
                                    $dir['path'] . DIRECTORY_SEPARATOR . $file,
                                    $dir['dir'] . '/' . basename($file)
                                );
                            } elseif (is_dir($filePath)) {
                                $dirs[] = [
                                    'dir' => $dir['dir'] . '/' . $file,
                                    'path' => $dir['path'] . DIRECTORY_SEPARATOR . $file
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
        $archivePath = $this->getPublicUploadDirectory() . $item;
        $folderPath = $this->getPublicUploadDirectory() . $this->canonicalizePath($destination) . DIRECTORY_SEPARATOR . $folderName;
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
            if (!file_exists($this->getPublicUploadDirectory() . $path)) {
                $error = 'file_not_found';
                return false;
            }
            if (is_dir($path) && $recursive === true) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($path),
                    RecursiveIteratorIterator::SELF_FIRST
                );
                foreach ($iterator as $item) {
                    $changed = chmod($this->getPublicUploadDirectory() . $item, octdec($perms));

                    if ($changed === false) {
                        $error = "permissions_change_failed";
                        return false;
                    }
                }
            }
            $success = chmod($this->getPublicUploadDirectory() . $path, octdec($perms));
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

    /**
     * Creates ASCII name
     *
     * @param string name encoded in UTF-8
     * @return string name containing only numbers, chars without diacritics, underscore and dash
     * @copyright Jakub Vrána, https://php.vrana.cz/
     */
    private function normalizeName($name)
    {
        $name = preg_replace('~[^\\pL0-9_]+~u', '-', $name);
        $name = trim($name, "-");
        $name = iconv("utf-8", "us-ascii//TRANSLIT", $name);
        $name = preg_replace('~[^-a-z0-9_]+~', '', $name);
        return $name;
    }
}
