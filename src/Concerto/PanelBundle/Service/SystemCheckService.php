<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Utils\SystemExecutableStatus;
use Concerto\PanelBundle\Utils\SystemPathStatus;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * Class responsible for displaying executable information based on configuration.
 */
class SystemCheckService {

    const DEFAULT_VERSION_FLAG = '--version';
    const DEFAULT_VERSION_PARSER = '((?:[\d]+)\.(?:[\d]+)\.(?:[\d]+))';

    public function __construct() {
        
    }

    public static function getDirNicename($name, $requirements) {
        return isset($requirements['nicename']) ? $requirements['nicename'] : "$name directory";
    }

    public function extractVersionFromLines($lines, $version_config) {
        $out = trim(join(' ', $lines));
        $version = null;

        $matches = array();

        // if parser expression is set, just execute it, else use a default one
        preg_match(
                '|' . ( isset($version_config['parser']) ? $version_config['parser'] : self::DEFAULT_VERSION_PARSER ) . '|', $out, $matches
        );

        if (isset($matches[1]))
            $version = trim($matches[1], ' .');

        return $version;
    }

    public function checkExecutable($name, $requirements) {
        if (!isset($requirements['command'])) {
            throw new InvalidConfigurationException(
            "'command' option is not set for required executable '$name'. Setup cannot continue."
            );
        }

        $cmd_line = $requirements['command'];

        $cmd_line.= " " . ( isset($requirements['version']['flag']) ?
                        $requirements['version']['flag'] : self::DEFAULT_VERSION_FLAG );

        $result = new SystemExecutableStatus($name, $requirements['command']);

        if (isset($requirements['link']))
            $result->setDocumentationLink($requirements['link']);

        $lines = array();
        $status = null;
        exec($cmd_line, $lines, $status);

        $result->setReturnCode($status);

        if (isset($requirements['version'])) {
            $result->setDetectedVersion($this->extractVersionFromLines($lines, $requirements['version']));

            if (isset($requirements['version']['min']))
                $result->setMinimalRequiredVersion($requirements['version']['min']);
        }

        return $result;
    }

    /**
     * Performs the actual check.
     */
    protected function verifyPathErrors($name, $requirements) {
        $path = $requirements['path'];

        $result = new SystemPathStatus($name, $path);

        $result->setPresent(file_exists($path));

        if (isset($requirements['type']))
            $result->setRequiredType($requirements['type']);

        if (is_dir($path))
            $result->setDetectedType('directory');
        if (is_file($path))
            $result->setDetectedType('file');
        if (is_link($path))
            $result->setDetectedType('symlink');

        if (isset($requirements['access']))
            $result->setRequiredAccess((array) $requirements['access']);

        $result->setDetectedAccess('read', is_readable($path));
        $result->setDetectedAccess('write', is_writeable($path));
        $result->setDetectedAccess('execute', is_executable($path));
        return $result;
    }

    protected function attemptToFix($fix_config) {
        if (isset($fix_config['dir']))
            chdir($fix_config['dir']);

        $commands = (array) $fix_config['command'];
        foreach ($commands as $command) {
            exec($command);
        }
    }

    public function checkPath($name, $requirements) {
        if (!isset($requirements['path'])) {
            $nicename = self::getDirNicename($name, $requirements);
            throw new InvalidConfigurationException(
            "'path' option is not set for required $nicename. Setup cannot continue."
            );
        }

        $result = $this->verifyPathErrors($name, $requirements);
        if (!$result->isOk() && isset($requirements['fix'])) {
            # try to fix, and recheck afterwards
            $this->attemptToFix($requirements['fix']);
            $result = $this->verifyPathErrors($name, $requirements);
            if (!$result->isOk() && isset($requirements['fix']['message']))
                $result->setAdditionalErrorMessage($requirements['fix']['message']);
            else
                $result->setFixed(true);
        }

        return $result;
    }

}
