<?php

namespace Concerto\TestBundle\Service;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Process\Process;
use Concerto\PanelBundle\Service\TestSessionService;
use Concerto\PanelBundle\Service\AdministrationService;
use Psr\Log\LoggerInterface;
use Concerto\PanelBundle\Service\LoadBalancerInterface;

class RRunnerService
{

    const OS_WIN = 0;
    const OS_LINUX = 1;

    private $root;
    private $panelNodes;
    private $settings;
    private $doctrine;
    private $logger;
    private $administrationService;
    private $testSessionCountService;
    private $loadBalancerService;
    private $environment;

    public function __construct($root, $panelNodes, $settings, RegistryInterface $doctrine, LoggerInterface $logger, AdministrationService $administrationService, TestSessionCountService $testSessionCountService, LoadBalancerInterface $loadBalancerService, $environment)
    {
        $this->root = $root;
        $this->panelNodes = $panelNodes;
        $this->settings = $settings;
        $this->doctrine = $doctrine;
        $this->logger = $logger;
        $this->administrationService = $administrationService;
        $this->testSessionCountService = $testSessionCountService;
        $this->loadBalancerService = $loadBalancerService;
        $this->environment = $environment;
    }

    private function authorizePanelNode($node_ip, $node_hash)
    {
        foreach ($this->panelNodes as $node) {
            if ($node_hash == $node["hash"]) {
                return $node;
            }
        }
        return false;
    }

    public function startR($panel_node_hash, $panel_node_port, $session_hash, $values, $client_ip, $client_browser, $calling_node_ip, $debug)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $panel_node_hash, $panel_node_port, $session_hash, $values, $client_ip, $client_browser, $debug");

        $response = array("source" => TestSessionService::SOURCE_PROCESS, "code" => TestSessionService::RESPONSE_STARTING);
        if ($panel_node = $this->authorizePanelNode($calling_node_ip, $panel_node_hash)) {
            $session_limit = $this->administrationService->getSessionLimit();
            $session_count = $this->testSessionCountService->getCurrentCount();
            if ($session_limit > 0 && $session_limit < $session_count + 1) {
                $response["code"] = TestSessionService::RESPONSE_SESSION_LIMIT_REACHED;
                return $response;
            }

            $panel_node_connection = $this->getSerializedConnection($panel_node);
            $client = json_encode(array(
                "ip" => $client_ip,
                "browser" => $client_browser
            ));
            $panel_node["port"] = $panel_node_port;
            $panel_node = json_encode($panel_node);
            $this->startProcess($panel_node, $panel_node_connection, $client, $session_hash, $values, $debug);
        } else {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - node $calling_node_ip / $panel_node_hash authentication failed.");
            $response["code"] = TestSessionService::RESPONSE_SESSION_LIMIT_REACHED;
        }
        return $response;
    }

    private function getSerializedConnection($node)
    {
        $con = $this->doctrine->getConnection($node["connection"]);
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

    private function getOutputFilePath($node_id, $session_hash)
    {
        return $this->getWorkingDirPath($node_id, $session_hash) . "concerto.log";
    }

    private function getPublicDirPath()
    {
        return $this->root . "/../src/Concerto/PanelBundle/Resources/public/files/";
    }

    private function getMediaUrl($node)
    {
        return $node["dir"] . "bundles/concertopanel/files/";
    }

    private function getWorkingDirPath($node_id, $session_hash)
    {
        $path = $this->root . "/../src/Concerto/TestBundle/Resources/sessions/$node_id/$session_hash/";
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
    private function getCommand($panel_node, $panel_node_connection, $client, $session_hash, $values, $debug)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $panel_node, $panel_node_connection, $client, $session_hash, $values, $debug");
        $max_idle_time = $this->settings["max_idle_time"];
        $max_exec_time = $this->settings["max_execution_time"];
        $keep_alive_interval_time = $this->settings["keep_alive_interval_time"];
        $keep_alive_tolerance_time = $this->settings["keep_alive_tolerance_time"];
        $renviron = "";
        if ($this->settings["r_environ_path"] != null) {
            //@TODO is addcslashes required below?
            $renviron = "--r_environ=\"" . addcslashes($this->settings["r_environ_path"], "\\") . "\"";
        }
        $decoded_panel_node = json_decode($panel_node, true);
        $decoded_test_node = $this->loadBalancerService->getLocalTestNode();
        $test_node = json_encode($decoded_test_node);
        switch ($this->getOS()) {
            case self::OS_WIN:
                return "start cmd /C \""
                    . "\"" . $this->escapeWindowsArg($this->settings["php_exec"]) . "\" "
                    . "\"" . $this->escapeWindowsArg($this->root) . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "console'\" concerto:r:start --env=" . $this->environment . " "
                    . "\"" . $this->escapeWindowsArg($this->settings["rscript_exec"]) . "\" "
                    . "\"" . $this->escapeWindowsArg($this->getIniFilePath()) . "\" "
                    . "\"" . $this->escapeWindowsArg($test_node) . "\" "
                    . "\"" . $this->escapeWindowsArg($panel_node) . "\" "
                    . "$session_hash "
                    . "\"" . $this->escapeWindowsArg($panel_node_connection) . "\" "
                    . "\"" . $this->escapeWindowsArg($client) . "\" "
                    . "\"" . $this->escapeWindowsArg($this->getWorkingDirPath($decoded_panel_node["id"], $session_hash)) . "\" "
                    . "\"" . $this->escapeWindowsArg($this->getPublicDirPath()) . "\" "
                    . "\"" . $this->escapeWindowsArg($this->getMediaUrl($decoded_test_node)) . "\" "
                    . "\"" . $this->escapeWindowsArg($this->getOutputFilePath($decoded_panel_node["id"], $session_hash)) . "\" "
                    . "$debug "
                    . "$max_idle_time "
                    . "$max_exec_time "
                    . "$keep_alive_interval_time "
                    . "$keep_alive_tolerance_time "
                    . "\"" . ($values ? $this->escapeWindowsArg($values) : "{}") . "\" "
                    . "$renviron "
                    . ">> "
                    . "\"" . $this->escapeWindowsArg($this->getOutputFilePath($decoded_panel_node["id"], $session_hash)) . "\" "
                    . "2>&1\"";
            default:
                return "nohup "
                    . $this->settings["php_exec"] . " "
                    . "'" . $this->root . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "console' concerto:r:start --env=" . $this->environment . " "
                    . "'" . $this->settings["rscript_exec"] . "' "
                    . "'" . $this->getIniFilePath() . "' "
                    . "'$test_node' "
                    . "'$panel_node' "
                    . "$session_hash "
                    . "'$panel_node_connection' "
                    . "'$client' "
                    . "'" . $this->getWorkingDirPath($decoded_panel_node["id"], $session_hash) . "' "
                    . "'" . $this->getPublicDirPath() . "' "
                    . "'" . $this->getMediaUrl($decoded_test_node) . "' "
                    . "'" . $this->getOutputFilePath($decoded_panel_node["id"], $session_hash) . "' "
                    . "$debug "
                    . "$max_idle_time "
                    . "$max_exec_time "
                    . "$keep_alive_interval_time "
                    . "$keep_alive_tolerance_time "
                    . ($values ? "'" . $values . "'" : "'{}'") . " "
                    . "$renviron "
                    . ">> "
                    . $this->getOutputFilePath($decoded_panel_node["id"], $session_hash) . " "
                    . "2>&1 & echo $!";
        }
    }

    private function startProcess($panel_node, $panel_node_connection, $client, $session_hash, $values, $debug)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $panel_node, $panel_node_connection, $client, $session_hash, $values, $debug");
        $command = $this->getCommand($panel_node, $panel_node_connection, $client, $session_hash, $values, $debug);
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
