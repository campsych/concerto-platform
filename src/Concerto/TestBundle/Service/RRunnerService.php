<?php

namespace Concerto\TestBundle\Service;

use Symfony\Component\Process\Process;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Psr\Log\LoggerInterface;

class RRunnerService {

    const OS_WIN = 0;
    const OS_LINUX = 1;

    private $root;
    private $nodes;
    private $settings;
    private $doctrine;
    private $logger;

    public function __construct($root, $nodes, $settings, Registry $doctrine, LoggerInterface $logger) {
        $this->root = $root;
        $this->nodes = $nodes;
        $this->settings = $settings;
        $this->doctrine = $doctrine;
        $this->logger = $logger;
    }

    private function authenticateNode($node_ip, $node_hash) {
        foreach ($this->nodes as $node) {
            if ($node_hash == $node["hash"]) {
                return $node;
            }
        }
        return false;
    }

    private function getRServerNode() {
        return $this->nodes[0];
    }

    public function startR($test_server_node_hash, $test_server_node_port, $session_hash, $values, $client_ip, $client_browser, $calling_node_ip, $debug) {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $test_server_node_hash, $test_server_node_port, $session_hash, $values, $client_ip, $client_browser, $debug");

        if ($test_server_node = $this->authenticateNode($calling_node_ip, $test_server_node_hash)) {
            $test_server_node_connection = $this->getSerializedConnection($test_server_node);
            $client = json_encode(array(
                "ip" => $client_ip,
                "browser" => $client_browser
            ));
            $test_server_node["port"] = $test_server_node_port;
            $test_server = json_encode($test_server_node);
            return $this->startProcess($test_server, $test_server_node_connection, $client, $session_hash, $values, $debug);
        } else {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - NODE $calling_node_ip / $test_server_node_hash AUTHENTICATION FAILED!");
        }
    }

    private function getSerializedConnection($node) {
        $con = $this->doctrine->getConnection($node["connection"]);
        $con_array = array(
            "driver" => $con->getDriver()->getName(),
            "host" => $con->getHost(),
            "port" => $con->getPort(),
            "dbname" => $con->getDatabase(),
            "username" => $con->getUsername(),
            "password" => $con->getPassword());
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

    //TODO proper OS detection
    private function getOS() {
        if (strpos(strtolower(PHP_OS), "win") !== false) {
            return self::OS_WIN;
        } else {
            return self::OS_LINUX;
        }
    }

    private function getIniFilePath() {
        return $this->root . "/../src/Concerto/TestBundle/Resources/R/initialization.R";
    }

    private function getOutputFilePath($node_id, $session_hash) {
        return $this->getWorkingDirPath($node_id, $session_hash) . "concerto.log";
    }

    private function getPublicDirPath() {
        return $this->root . "/../src/Concerto/PanelBundle/Resources/public/files/";
    }

    private function getMediaUrl($node) {
        return $node["protocol"] . "://" . $node["host"] . $node["dir"] . "bundles/concertopanel/files/";
    }

    private function getWorkingDirPath($node_id, $session_hash) {
        $path = $this->root . "/../src/Concerto/TestBundle/Resources/sessions/$node_id/$session_hash/";
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
        return $path;
    }

    //TODO must not send plain password through command line
    private function getCommand($test_server, $test_server_node_connection, $client, $session_hash, $values, $debug) {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $test_server, $test_server_node_connection, $client, $session_hash, $values, $debug");
        $max_idle_time = $this->settings["max_idle_time"];
        $max_exec_time = $this->settings["max_execution_time"];
        $keep_alive_interval_time = $this->settings["keep_alive_interval_time"];
        $keep_alive_tolerance_time = $this->settings["keep_alive_tolerance_time"];
        $renviron = "";
        if ($this->settings["r_environ_path"] != null) {
            $renviron = "--r_environ=\"" . $this->settings["r_environ_path"] . "\"";
        }
        $decoded_test_server = json_decode($test_server, true);
        $decoded_r_server_node = $this->getRServerNode();
        $r_server_node = json_encode($decoded_r_server_node);
        switch ($this->getOS()) {
            case self::OS_WIN:
                $cmd = "start cmd /C \"\"" . $this->settings["php_exec"] . "\" \"" . $this->root . "/console\" concerto:r:start \"" . $this->settings["rscript_exec"] . "\" \"" . $this->getIniFilePath() . "\" \"" . addcslashes($r_server_node, '"') . "\" \"" . addcslashes($test_server, '"') . "\" $session_hash \"" . addcslashes($test_server_node_connection, '"') . "\" \"" . addcslashes($client, '"') . "\" \"" . $this->getWorkingDirPath($decoded_test_server["id"], $session_hash) . "\" \"" . $this->getPublicDirPath() . "\" \"" . $this->getMediaUrl($decoded_r_server_node) . "\" \"" . $this->getOutputFilePath($decoded_test_server["id"], $session_hash) . "\" $debug $max_idle_time $max_exec_time $keep_alive_interval_time $keep_alive_tolerance_time \"" . ($values ? addcslashes($values, '"') : "") . "\" $renviron >> \"" . $this->getOutputFilePath($decoded_test_server["id"], $session_hash) . "\" 2>&1\"";
                $cmd = str_replace("(", "^(", $cmd);
                $cmd = str_replace(")", "^)", $cmd);
                return $cmd;
            default:
                return "nohup " . $this->settings["php_exec"] . " '" . $this->root . "/console' concerto:r:start '" . $this->settings["rscript_exec"] . "' '" . $this->getIniFilePath() . "' '$r_server_node' '$test_server' $session_hash '$test_server_node_connection' '$client' '" . $this->getWorkingDirPath($decoded_test_server["id"], $session_hash) . "' '" . $this->getPublicDirPath() . "' '" . $this->getMediaUrl($decoded_r_server_node) . "' '" . $this->getOutputFilePath($decoded_test_server["id"], $session_hash) . "' $debug $max_idle_time $max_exec_time $keep_alive_interval_time $keep_alive_tolerance_time " . ($values ? "'" . $values . "'" : "") . " $renviron >> " . $this->getOutputFilePath($decoded_test_server["id"], $session_hash) . " 2>&1 & echo $!";
        }
    }

    private function startProcess($test_server, $test_server_node_connection, $client, $session_hash, $values, $debug) {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $test_server, $test_server_node_connection, $client, $session_hash, $values, $debug");
        $command = $this->getCommand($test_server, $test_server_node_connection, $client, $session_hash, $values, $debug);
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . ":command: $command");

        $process = new Process($command);
        $process->mustRun();
        $this->logger->info($process->getOutput());
        $this->logger->info($process->getErrorOutput());
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . ": status: " . $process->getStatus() . " / " . $process->getExitCode());

        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . ": process initiation finished");
    }

}
