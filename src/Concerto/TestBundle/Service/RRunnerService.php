<?php

namespace Concerto\TestBundle\Service;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Process\Process;
use Concerto\PanelBundle\Service\TestSessionService;
use Concerto\PanelBundle\Service\AdministrationService;
use Psr\Log\LoggerInterface;

class RRunnerService
{

    const OS_WIN = 0;
    const OS_LINUX = 1;

    private $root;
    private $testRunnerSettings;
    private $doctrine;
    private $logger;
    private $administrationService;
    private $testSessionCountService;
    private $environment;

    public function __construct($root, $settings, RegistryInterface $doctrine, LoggerInterface $logger, AdministrationService $administrationService, TestSessionCountService $testSessionCountService, $environment)
    {
        $this->root = $root;
        $this->testRunnerSettings = $settings;
        $this->doctrine = $doctrine;
        $this->logger = $logger;
        $this->administrationService = $administrationService;
        $this->testSessionCountService = $testSessionCountService;
        $this->environment = $environment;
    }

    public function startR($panel_node_port, $session_hash, $values, $client_ip, $client_browser, $debug)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $panel_node_port, $session_hash, $values, $client_ip, $client_browser, $debug");

        $response = array("source" => TestSessionService::SOURCE_PROCESS, "code" => TestSessionService::RESPONSE_STARTING);

        $session_limit = $this->administrationService->getSessionLimit();
        $session_count = $this->testSessionCountService->getCurrentCount();
        if ($session_limit > 0 && $session_limit < $session_count + 1) {
            $response["code"] = TestSessionService::RESPONSE_SESSION_LIMIT_REACHED;
            return $response;
        }

        $panel_node_connection = $this->getSerializedConnection();
        $client = json_encode(array(
            "ip" => $client_ip,
            "browser" => $client_browser
        ));
        $this->startProcess($panel_node_port, $panel_node_connection, $client, $session_hash, $values, $debug);

        return $response;
    }

    private function getSerializedConnection()
    {
        $con = $this->doctrine->getConnection("local");
        $con_array = array(
            "driver" => $con->getDriver()->getName(),
            "host" => $con->getHost(),
            "port" => $con->getPort(),
            "dbname" => $con->getDatabase(),
            "username" => $con->getUsername(),
            "password" => $con->getPassword());

        //@TODO there should be no default port
        if (!$con_array["port"]) {
            $con_array["port"] = 3306;
        }
        $params = $con->getParams();
        if (array_key_exists("path", $params)) {
            $con_array["path"] = $params["path"];
        }
        if (array_key_exists("unix_socket", $params)) {
            $con_array["unix_socket"] = $params["unix_socket"];
        }
        $json_connection = json_encode($con_array);
        return $json_connection;
    }

    //@TODO proper OS detection
    public function getOS()
    {
        if (strpos(strtolower(PHP_OS), "win") !== false) {
            return self::OS_WIN;
        } else {
            return self::OS_LINUX;
        }
    }

    public function getIniFilePath()
    {
        return $this->root . "/../src/Concerto/TestBundle/Resources/R/standalone.R";
    }

    private function getOutputFilePath($session_hash)
    {
        return $this->getWorkingDirPath($session_hash) . "concerto.log";
    }

    private function getPublicDirPath()
    {
        return $this->root . "/../src/Concerto/PanelBundle/Resources/public/files/";
    }

    private function getMediaUrl()
    {
        return $this->testRunnerSettings["dir"] . "bundles/concertopanel/files/";
    }

    private function getWorkingDirPath($session_hash)
    {
        $path = $this->root . "/../src/Concerto/TestBundle/Resources/sessions/$session_hash/";
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
        return $path;
    }

    private function escapeWindowsArg($arg)
    {
        $arg = addcslashes($arg, '"');
        $arg = str_replace("(", "^(", $arg);
        $arg = str_replace(")", "^)", $arg);
        return $arg;
    }

    //@TODO must not send plain password through command line
    private function getCommand($panel_node_port, $panel_node_connection, $client, $session_hash, $values, $debug)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $panel_node_port, $panel_node_connection, $client, $session_hash, $values, $debug");
        $keep_alive_interval_time = $this->testRunnerSettings["keep_alive_interval_time"];
        $keep_alive_tolerance_time = $this->testRunnerSettings["keep_alive_tolerance_time"];
        $renviron = "";
        if ($this->testRunnerSettings["r_environ_path"] != null) {
            //@TODO is addcslashes required below?
            $renviron = "--r_environ=\"" . addcslashes($this->testRunnerSettings["r_environ_path"], "\\") . "\"";
        }
        switch ($this->getOS()) {
            case self::OS_WIN:
                return "start cmd /C \""
                    . "\"" . $this->escapeWindowsArg($this->testRunnerSettings["php_exec"]) . "\" "
                    . "\"" . $this->escapeWindowsArg($this->root) . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "console'\" concerto:r:start --env=" . $this->environment . " "
                    . "\"" . $this->escapeWindowsArg($this->getIniFilePath()) . "\" "
                    . "$panel_node_port "
                    . "$session_hash "
                    . "\"" . $this->escapeWindowsArg($panel_node_connection) . "\" "
                    . "\"" . $this->escapeWindowsArg($client) . "\" "
                    . "\"" . $this->escapeWindowsArg($this->getWorkingDirPath($session_hash)) . "\" "
                    . "\"" . $this->escapeWindowsArg($this->getPublicDirPath()) . "\" "
                    . "\"" . $this->escapeWindowsArg($this->getMediaUrl()) . "\" "
                    . "\"" . $this->escapeWindowsArg($this->getOutputFilePath($session_hash)) . "\" "
                    . "$debug "
                    . "$keep_alive_interval_time "
                    . "$keep_alive_tolerance_time "
                    . "\"" . ($values ? $this->escapeWindowsArg($values) : "{}") . "\" "
                    . "$renviron "
                    . ">> "
                    . "\"" . $this->escapeWindowsArg($this->getOutputFilePath($session_hash)) . "\" "
                    . "2>&1\"";
            default:
                return "nohup "
                    . $this->testRunnerSettings["php_exec"] . " "
                    . "'" . $this->root . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "console' concerto:r:start --env=" . $this->environment . " "
                    . "'" . $this->getIniFilePath() . "' "
                    . "$panel_node_port "
                    . "$session_hash "
                    . "'$panel_node_connection' "
                    . "'$client' "
                    . "'" . $this->getWorkingDirPath($session_hash) . "' "
                    . "'" . $this->getPublicDirPath() . "' "
                    . "'" . $this->getMediaUrl() . "' "
                    . "'" . $this->getOutputFilePath($session_hash) . "' "
                    . "$debug "
                    . "$keep_alive_interval_time "
                    . "$keep_alive_tolerance_time "
                    . ($values ? "'" . $values . "'" : "'{}'") . " "
                    . "$renviron "
                    . ">> "
                    . $this->getOutputFilePath($session_hash) . " "
                    . "2>&1 & echo $!";
        }
    }

    private function startProcess($panel_node_port, $panel_node_connection, $client, $session_hash, $values, $debug)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $panel_node_port, $panel_node_connection, $client, $session_hash, $values, $debug");
        $command = $this->getCommand($panel_node_port, $panel_node_connection, $client, $session_hash, $values, $debug);
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . ":command: $command");

        $process = new Process($command);
        $process->mustRun();
        $this->logger->info($process->getOutput());
        $this->logger->info($process->getErrorOutput());
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . ": status: " . $process->getStatus() . " / " . $process->getExitCode());
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . ": process initiation finished");

        return $process->getExitCode();
    }

    public function getUploadDirectory()
    {
        return dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'PanelBundle' . DIRECTORY_SEPARATOR . ($this->environment === "test" ? ("Tests" . DIRECTORY_SEPARATOR) : "") . "Resources" . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "files" . DIRECTORY_SEPARATOR;
    }

    public function uploadFile($session_hash, $calling_node_ip, $files, $name)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $calling_node_ip, $name");

        $response = array("result" => -1);
        foreach ($files as $file) {
            $upload_path = $this->getUploadDirectory() . $file->getClientOriginalName();
            $upload_result = move_uploaded_file($file->getRealPath(), $upload_path);
            if ($upload_result)
                $response = array("result" => 0, "file_path" => $this->getUploadDirectory() . $file->getClientOriginalName(), "name" => $name);
            else {
                $response = array("result" => -1);
                break;
            }
        }
        return json_encode($response);
    }

}
