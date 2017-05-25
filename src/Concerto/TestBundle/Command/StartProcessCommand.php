<?php

namespace Concerto\TestBundle\Command;

use Concerto\TestBundle\Service\RRunnerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Concerto\TestBundle\Service\TestSessionCountService;
use Concerto\PanelBundle\Service\TestSessionService;
use Psr\Log\LoggerInterface;

class StartProcessCommand extends Command {

    private $panelNode;
    private $output;
    private $lastProcessTime;   //for max execution timeout
    private $lastClientTime;    //for idle timeout
    private $lastKeepAliveTime; //for keep alive timeout
    private $maxExecTime;
    private $maxIdleTime;
    private $keepAliveIntervalTime;
    private $keepAliveToleranceTime;
    private $isSerializing;
    private $isWaitingForProcess;
    private $isDebug;
    private $currentTotalDebugData = "";
    private $logPath;
    private $rLogPath;
    private $rEnviron;
    private $sessionCountService;
    private $logger;

    public function __construct(TestSessionCountService $sessionCountService, LoggerInterface $logger) {
        parent::__construct();

        $this->sessionCountService = $sessionCountService;
        $this->logger = $logger;
    }

    protected function configure() {
        $this->isSerializing = false;
        $this->isWaitingForProcess = false;
        $this->isDebug = false;
        $this->currentTotalDebugData = "";
        $this->setName("concerto:r:start")->setDescription("Starts new R session.");
        $this->addArgument("rscript_exec_path", InputArgument::REQUIRED, "Rscript executable file path");
        $this->addArgument("ini_path", InputArgument::REQUIRED, "initialization file path");
        $this->addArgument("test_node", InputArgument::REQUIRED, "test node json serialized data");
        $this->addArgument("panel_node", InputArgument::REQUIRED, "panel node json serialized data");
        $this->addArgument("test_session_id", InputArgument::REQUIRED, "test session id");
        $this->addArgument("panel_node_connection", InputArgument::REQUIRED, "panel node connection json serialized data");
        $this->addArgument("client", InputArgument::REQUIRED, "client json serialized data");
        $this->addArgument("working_directory", InputArgument::REQUIRED, "session working directory");
        $this->addArgument("public_directory", InputArgument::REQUIRED, "public directory");
        $this->addArgument("media_url", InputArgument::REQUIRED, "media URL");
        $this->addArgument("log_path", InputArgument::REQUIRED, "log path");
        $this->addArgument("debug", InputArgument::REQUIRED, "debug test execution");
        $this->addArgument("max_idle_time", InputArgument::REQUIRED, "max time without any R code interpretation");
        $this->addArgument("max_exec_time", InputArgument::REQUIRED, "max time R code can be interpreted");
        $this->addArgument("keep_alive_interval_time", InputArgument::REQUIRED, "keep-alive interval time");
        $this->addArgument("keep_alive_tolerance_time", InputArgument::REQUIRED, "keep-alive tolerance time");
        $this->addArgument("submit", InputArgument::OPTIONAL, "submitted variables");

        $this->addOption("r_environ", "renv", InputOption::VALUE_OPTIONAL, "R Renviron file path", null);
    }

    private function log($fun, $msg = null) {
        $this->output->write(__CLASS__ . ":" . $fun . ($msg ? " - $msg" : ""), true);
        $this->logger->info(__CLASS__ . ":" . $fun . ($msg ? " - $msg" : ""));
    }

    private function createListenerSocket() {
        $this->log(__FUNCTION__);

        if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
            return false;
        }
        if (socket_bind($sock, "0.0.0.0") === false) {
            return false;
        }
        if (socket_listen($sock, SOMAXCONN) === false) {
            return false;
        }
        socket_set_nonblock($sock);
        return $sock;
    }

    private function createPanelNodeResponseSocket() {
        $this->log(__FUNCTION__);

        if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
            return false;
        }
        if (socket_connect($sock, gethostbyname($this->panelNode->sock_host), $this->panelNode->port) === false) {
            $this->log(__FUNCTION__, "socket_connect failed: " . socket_strerror(socket_last_error()));
            return false;
        }
        return $sock;
    }

    private function startListener($server_sock, $submitter_sock) {
        $this->log(__FUNCTION__);

        $this->lastClientTime = time();
        $this->lastKeepAliveTime = time();
        $this->lastProcessTime = time();
        do {
            $this->checkIdleTimeout($submitter_sock) || $this->checkKeepAliveTimeout($submitter_sock);
            if ($this->checkExecutionTimeout()) {
                break;
            }
            if (($client_sock = @socket_accept($server_sock)) === false) {
                continue;
            }

            $this->log(__FUNCTION__, "socket accepted");

            if (false === ($buf = socket_read($client_sock, 8388608, PHP_NORMAL_READ))) {
                continue;
            }
            if (!$msg = trim($buf)) {
                continue;
            }

            $this->log(__FUNCTION__, $msg);
            if ($this->interpretMessage($submitter_sock, $msg)) {
                break;
            }
        } while (usleep(100 * 1000) || true);

        $this->log(__FUNCTION__, "listener ended");
    }

    private function checkExecutionTimeout() {
        if (time() - $this->lastProcessTime > $this->maxExecTime && $this->isWaitingForProcess) {
            $this->log(__FUNCTION__, "execution timeout reached");

            $this->respondToPanelNode(json_encode(array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_ERROR
            )));
            return true;
        } else {
            return false;
        }
    }

    private function checkIdleTimeout($submitter_sock) {
        if (time() - $this->lastClientTime > $this->maxIdleTime && !$this->isSerializing) {
            $this->log(__FUNCTION__, "idle timeout reached");

            $this->isSerializing = true;
            $this->respondToProcess($submitter_sock, json_encode(array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_SERIALIZE
            )));
            return true;
        } else {
            return false;
        }
    }

    private function checkKeepAliveTimeout($submitter_sock) {
        if ($this->keepAliveIntervalTime > 0 && time() - $this->lastKeepAliveTime > $this->keepAliveIntervalTime + $this->keepAliveToleranceTime && !$this->isSerializing) {
            $this->log(__FUNCTION__, "keep alive timeout reached");

            $this->isSerializing = true;
            $this->respondToProcess($submitter_sock, json_encode(array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_SERIALIZE
            )));
            return true;
        } else {
            return false;
        }
    }

    private function interpretMessage($submitter_sock, $message) {
        $this->log(__FUNCTION__, $message);

        $msg = json_decode($message);
        switch ($msg->source) {
            case TestSessionService::SOURCE_PROCESS: {
                    return $this->interpretProcessMessage($message);
                }
            case TestSessionService::SOURCE_PANEL_NODE: {
                    return $this->interpretPanelNodeMessage($submitter_sock, $message);
                }
        }
    }

    private function interpretPanelNodeMessage($submitter_sock, $message) {
        $this->log(__FUNCTION__, $message);

        $msg = json_decode($message);
        switch ($msg->code) {
            case TestSessionService::RESPONSE_SUBMIT: {
                    $this->isWaitingForProcess = true;
                    $this->panelNode = $msg->panelNode;
                    $this->lastClientTime = time();
                    $this->lastKeepAliveTime = time();
                    $this->respondToProcess($submitter_sock, $message);
                    return false;
                }
            case TestSessionService::RESPONSE_KEEPALIVE_CHECKIN: {
                    $this->lastKeepAliveTime = time();
                    return false;
                }
        }
    }

    private function interpretProcessMessage($message) {
        $this->log(__FUNCTION__, $message);

        $this->isWaitingForProcess = false;
        $this->lastProcessTime = time();
        $msg = json_decode($message, true);
        switch ($msg["code"]) {
            case TestSessionService::RESPONSE_VIEW_TEMPLATE: {
                    $this->respondToPanelNode($message);
                    return false;
                }
            case TestSessionService::RESPONSE_UNRESUMABLE:
            case TestSessionService::RESPONSE_ERROR:
            case TestSessionService::RESPONSE_FINISHED:
            case TestSessionService::RESPONSE_VIEW_FINAL_TEMPLATE: {
                    $this->sessionCountService->updateCountRecord();
                    $this->respondToPanelNode($message);
                    return true;
                }
            case TestSessionService::RESPONSE_SERIALIZATION_FINISHED: {
                    $this->sessionCountService->updateCountRecord();
                    return true;
                }
        }
    }

    private function respondToProcess($submitter_sock, $response) {
        $this->log(__FUNCTION__, $response);

        $this->lastProcessTime = time();
        do {
            if (($client_sock = socket_accept($submitter_sock)) === false) {
                usleep(10000);
                continue;
            }

            $this->log(__FUNCTION__, "socket accepted");

            socket_write($client_sock, $response . "\n");
            break;
        } while (true);
        $this->log(__FUNCTION__, "submitter ended");
    }

    private function respondToPanelNode($response) {
        $this->log(__FUNCTION__);

        if ($this->isDebug) {
            $response = $this->appendDebugDataToResponse($response);
        }

        $this->log(__FUNCTION__, $response);

        $resp_sock = $this->createPanelNodeResponseSocket();
        socket_write($resp_sock, $response . "\n");
        socket_close($resp_sock);
    }

    private function appendDebugDataToResponse($response) {
        if (file_exists($this->rLogPath)) {
            $new_data = file_get_contents($this->rLogPath, false, null, strlen($this->currentTotalDebugData));
            $this->currentTotalDebugData .= $new_data;
            $decoded_response = json_decode($response, true);
            $decoded_response["debug"] = mb_convert_encoding($new_data, "UTF-8");
            $response = json_encode($decoded_response);
        }
        return $response;
    }

    //TODO proper OS detection
    private function getOS() {
        if (strpos(strtolower(PHP_OS), "win") !== false) {
            return RRunnerService::OS_WIN;
        } else {
            return RRunnerService::OS_LINUX;
        }
    }

    private function escapeWindowsArg($arg) {
        $arg = addcslashes($arg, '"');
        $arg = str_replace("(", "^(", $arg);
        $arg = str_replace(")", "^)", $arg);
        return $arg;
    }

    private function getCommand($rscript_exec, $ini_path, $panel_node_connection, $test_node, $submitter, $client, $test_session_id, $wd, $pd, $murl, $values) {
        switch ($this->getOS()) {
            case RRunnerService::OS_LINUX:
                return "nohup " . $rscript_exec . " --no-save --no-restore --quiet "
                        . "'$ini_path' "
                        . "'$panel_node_connection' "
                        . "'$test_node' "
                        . "'$submitter' "
                        . "'$client' "
                        . "$test_session_id "
                        . "'$wd' "
                        . "'$pd' "
                        . "'$murl' "
                        . "'$values' "
                        . ">> "
                        . "'" . $this->logPath . "' "
                        . "> "
                        . "'" . $this->rLogPath . "' "
                        . "2>&1 & echo $!";
            default:
                return "start cmd /C \""
                        . "\"" . $this->escapeWindowsArg($rscript_exec) . "\" --no-save --no-restore --quiet "
                        . "\"" . $this->escapeWindowsArg($ini_path) . "\" "
                        . "\"" . $this->escapeWindowsArg($panel_node_connection) . "\" "
                        . "\"" . $this->escapeWindowsArg($test_node) . "\" "
                        . "\"" . $this->escapeWindowsArg($submitter) . "\" "
                        . "\"" . $this->escapeWindowsArg($client) . "\" "
                        . "$test_session_id "
                        . "\"" . $this->escapeWindowsArg($wd) . "\" "
                        . "\"" . $this->escapeWindowsArg($pd) . "\" "
                        . "$murl "
                        . "\"" . ($values ? $this->escapeWindowsArg($values) : "{}") . "\" "
                        . ">> "
                        . "\"" . $this->escapeWindowsArg($this->logPath) . "\" "
                        . "> "
                        . "\"" . $this->escapeWindowsArg($this->rLogPath) . "\" "
                        . "2>&1\"";
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->output = $output;
        $this->log(__FUNCTION__);

        if ($this->getOS() == RRunnerService::OS_LINUX) {
            if (posix_getpid() != posix_getsid(getmypid())) {
                posix_setsid();
            }
        }

        $rscript_exec = $input->getArgument("rscript_exec_path");
        $panel_node = $input->getArgument("panel_node");
        $this->panelNode = json_decode($panel_node);
        $panel_node_connection = $input->getArgument("panel_node_connection");
        $client = $input->getArgument("client");
        $ini_path = $input->getArgument("ini_path");
        $test_session_id = $input->getArgument("test_session_id");
        $wd = $input->getArgument("working_directory");
        $pd = $input->getArgument("public_directory");
        $murl = $input->getArgument("media_url");
        $this->logPath = $input->getArgument("log_path");
        $this->rLogPath = $this->logPath . ".r";
        $values = $input->getArgument("submit");
        $this->isDebug = $input->getArgument("debug") == 1;
        if (!$values) {
            $values = "";
        }
        $this->maxExecTime = $input->getArgument("max_exec_time");
        $this->maxIdleTime = $input->getArgument("max_idle_time");
        $this->keepAliveIntervalTime = $input->getArgument("keep_alive_interval_time");
        $this->keepAliveToleranceTime = $input->getArgument("keep_alive_tolerance_time");
        $this->rEnviron = $input->getOption("r_environ");

        $test_node = $input->getArgument("test_node");
        $decoded_test_node = json_decode($test_node, true);

        $test_node_sock = $this->createListenerSocket();
        socket_getsockname($test_node_sock, $test_node_ip, $test_node_port);
        $decoded_test_node = json_decode($test_node, true);
        $decoded_test_node["port"] = $test_node_port;
        $test_node = json_encode($decoded_test_node);

        $submitter_sock = $this->createListenerSocket();
        socket_getsockname($submitter_sock, $submitter_ip, $submitter_port);
        $submitter = json_encode(array("host" => $submitter_ip, "port" => $submitter_port));

        $cmd = $this->getCommand($rscript_exec, $ini_path, $panel_node_connection, $test_node, $submitter, $client, $test_session_id, $wd, $pd, $murl, $values);

        $this->log(__FUNCTION__, $cmd);

        $process = new Process($cmd);
        $process->setEnhanceWindowsCompatibility(false);
        if ($this->rEnviron != null) {
            $this->log(__FUNCTION__, "setting process renviron to: " . $this->rEnviron);
            $env = array();
            $env["R_ENVIRON"] = $this->rEnviron;
            $process->setEnv($env);
        }
        $this->sessionCountService->updateCountRecord(1);
        $process->mustRun();
        $this->isWaitingForProcess = true;
        $this->startListener($test_node_sock, $submitter_sock);
        socket_close($submitter_sock);
        socket_close($test_node_sock);
        $this->log(__FUNCTION__, "closing process");
    }

}
