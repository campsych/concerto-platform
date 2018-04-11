<?php

namespace Concerto\TestBundle\Service;

use Concerto\PanelBundle\Entity\TestSession;
use Concerto\PanelBundle\Repository\TestSessionRepository;
use Concerto\PanelBundle\Service\AdministrationService;
use Concerto\PanelBundle\Service\TestSessionService;
use Psr\Log\LoggerInterface;

class PersistantSessionRunnerService extends ASessionRunnerService
{
    private $logger;
    private $testSessionRepository;
    private $administrationService;

    public function __construct(LoggerInterface $logger, TestSessionRepository $testSessionRepository, AdministrationService $administrationService)
    {
        $this->logger = $logger;
        $this->testSessionRepository = $testSessionRepository;
        $this->administrationService = $administrationService;
    }

    public function startNew(TestSession $session, $params, $client_ip, $client_browser, $debug = false)
    {
        $session_hash = $session->getHash();
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $params, $client_ip, $client_ip, $client_browser, $debug");
        if (($panel_node_sock = $this->createListenerSocket("127.0.0.1", null)) === false) {
            return array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_ERROR
            );
        }
        socket_getsockname($panel_node_sock, $panel_node_host, $panel_node_port);

        $session->setPanelNodePort($panel_node_port);
        $this->testSessionRepository->save($session);

        $rresult = $this->startR($panel_node_port, $session_hash, null, $client_ip, $client_browser, $debug ? 1 : 0);

        $this->testSessionRepository->clear();
        $session = $this->testSessionRepository->findOneBy(array("hash" => $session_hash));
        $response = null;
        switch ($rresult["code"]) {
            case self::RESPONSE_SESSION_LIMIT_REACHED:
                {
                    $response = $rresult;
                    $session->setStatus(self::STATUS_REJECTED);
                    $this->testSessionRepository->save($session);
                    $this->administrationService->insertSessionLimitMessage($session);
                    socket_close($panel_node_sock);
                    break;
                }
            default:
                {
                    $response = $this->startListener($panel_node_sock, $session_hash);
                    $this->testSessionRepository->clear();
                    break;
                }
        }

        return $response;
    }

    public function submit(TestSession $session, $values, $client_ip, $client_browser, $time = null)
    {
        $session_hash = $session->getHash();
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $values, $client_ip, $client_browser, $time");
        if (($client_sock = $this->createListenerSocket("127.0.0.1", $session_hash)) === false) {
            return array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_ERROR
            );
        }
        socket_getsockname($client_sock, $panel_node_ip, $panel_node_port);

        $test_node_port = $session->getTestNodePort();

        $session->setPanelNodePort($panel_node_port);
        $this->testSessionRepository->save($session);
        $this->testSessionRepository->clear();

        $submitted = $this->submitToTestNode($test_node_port, $panel_node_port, $client_ip, $client_browser, $values);
        if (!$submitted) {
            return array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_SESSION_LOST,
                "systemError" => "Submit to process failed."
            );
        }

        $response = $this->startListener($client_sock, $session_hash);
        return $response;
    }

    public function backgroundWorker(TestSession $session, $values, $client_ip, $client_browser, $time = null)
    {
        $session_hash = $session->getHash();
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $values, $client_ip, $client_browser, $time");
        if (($client_sock = $this->createListenerSocket("127.0.0.1", $session_hash)) === false) {
            return array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_ERROR
            );
        }
        socket_getsockname($client_sock, $panel_node_ip, $panel_node_port);

        $test_node_port = $session->getTestNodePort();

        $session->setPanelNodePort($panel_node_port);
        $this->testSessionRepository->save($session);
        $this->testSessionRepository->clear();

        $submitted = $this->submitToTestNode($test_node_port, $panel_node_port, $client_ip, $client_browser, $values, TestSessionService::RESPONSE_WORKER);
        if (!$submitted) {
            return array(
                "source" => TestSessionService::SOURCE_PANEL_NODE,
                "code" => TestSessionService::RESPONSE_SESSION_LOST,
                "systemError" => "Submit to process failed."
            );
        }

        $response = $this->startListener($client_sock, $session_hash);
        return $response;
    }

    public function keepAlive(TestSession $session, $client_ip, $client_browser)
    {
        $session_hash = $session->getHash();
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $client_ip, $client_browser");
        $test_node_port = $session->getTestNodePort();

        if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
            return false;
        }
        if (socket_connect($sock, "127.0.0.1", $test_node_port) === false) {
            socket_close($sock);
            return false;
        }
        socket_write($sock, json_encode(array(
                "source" => TestSessionService::SOURCE_PANEL_NODE,
                "code" => TestSessionService::RESPONSE_KEEPALIVE_CHECKIN,
                "client" => array("ip" => $client_ip, "browser" => $client_browser)
            )) . "\n");
        socket_close($sock);
        return true;
    }

    public function kill(TestSession $session, $client_ip, $client_browser)
    {
        $session_hash = $session->getHash();
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $client_ip, $client_browser");
        $test_node_port = $session->getTestNodePort();
        if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
            return false;
        }
        if (socket_connect($sock, "127.0.0.1", $test_node_port) === false) {
            socket_close($sock);
            return false;
        }
        socket_write($sock, json_encode(array(
                "source" => TestSessionService::SOURCE_PANEL_NODE,
                "code" => TestSessionService::RESPONSE_STOP,
                "client" => array("ip" => $client_ip, "browser" => $client_browser)
            )) . "\n");
        socket_close($sock);
        return true;
    }

    private function submitToTestNode($test_node_port, $panel_node_port, $client_ip, $client_browser, $values, $code = TestSessionService::RESPONSE_SUBMIT)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $test_node_port, $panel_node_port, $client_ip, $client_browser, $values");
        if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
            return false;
        }
        if (socket_connect($sock, "127.0.0.1", $test_node_port) === false) {
            socket_close($sock);
            return false;
        }
        socket_write($sock, json_encode(array(
                "source" => TestSessionService::SOURCE_PANEL_NODE,
                "code" => $code,
                "panelNodePort" => $panel_node_port,
                "client" => array("ip" => $client_ip, "browser" => $client_browser),
                "values" => $values
            )) . "\n");
        socket_close($sock);
        return true;
    }

    private function startListener($sock, $session_hash)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__);
        $response = "";

        $client_sock = @socket_accept($sock);
        if ($client_sock === false) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . ":socket_accept() failed, " . socket_strerror(socket_last_error($sock)) . ", $session_hash");
        } else {
            do {
                $buf = socket_read($client_sock, 8388608, PHP_NORMAL_READ);
                if ($buf === false) {
                    $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . ":socket_read() failed, " . socket_strerror(socket_last_error($client_sock)) . ", $session_hash");
                    break;
                }
                $buf = trim($buf);
                if (!$buf) {
                    continue;
                }

                $response .= $buf;
                break;
            } while (usleep(100 * 1000) || true);
            socket_close($client_sock);
        }

        socket_close($sock);

        return json_decode($response, true);
    }

    private function createListenerSocket($ip, $session_hash)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $ip");
        if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . ":socket_create() failed, " . socket_strerror(socket_last_error()) . ", $session_hash");
            return false;
        }
        if (socket_bind($sock, "0.0.0.0") === false) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . ":socket_bind() failed, " . socket_strerror(socket_last_error($sock)) . ", $session_hash");
            socket_close($sock);
            return false;
        }
        if (socket_listen($sock, SOMAXCONN) === false) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . ":socket_listen() failed, " . socket_strerror(socket_last_error($sock)) . ", $session_hash");
            socket_close($sock);
            return false;
        }
        return $sock;
    }

    private function startR($panel_node_port, $session_hash, $values, $client_ip, $client_browser, $debug)
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
    private function getOS()
    {
        if (strpos(strtolower(PHP_OS), "win") !== false) {
            return self::OS_WIN;
        } else {
            return self::OS_LINUX;
        }
    }

    private function getIniFilePath()
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
}