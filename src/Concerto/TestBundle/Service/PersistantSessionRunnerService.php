<?php

namespace Concerto\TestBundle\Service;

use Concerto\PanelBundle\Entity\TestSession;
use Concerto\PanelBundle\Repository\TestSessionRepository;
use Concerto\PanelBundle\Service\AdministrationService;
use Concerto\PanelBundle\Service\TestSessionService;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Process\Process;

class PersistantSessionRunnerService extends ASessionRunnerService
{
    const OS_WIN = 0;
    const OS_LINUX = 1;

    private $testSessionRepository;
    private $administrationService;
    private $testSessionCountService;
    private $environment;

    public function __construct(LoggerInterface $logger, TestSessionRepository $testSessionRepository, AdministrationService $administrationService, TestSessionCountService $testSessionCountService, RegistryInterface $doctrine, $testRunnerSettings, $root, $environment)
    {
        parent::__construct($logger, $testRunnerSettings, $root, $doctrine);

        $this->testSessionRepository = $testSessionRepository;
        $this->administrationService = $administrationService;
        $this->testSessionCountService = $testSessionCountService;
        $this->environment = $environment;
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
            case TestSessionService::RESPONSE_SESSION_LIMIT_REACHED:
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

        $client = json_encode(array(
            "ip" => $client_ip,
            "browser" => $client_browser
        ));
        $this->startProcess($panel_node_port, $client, $session_hash, $values, $debug);

        return $response;
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

    private function escapeWindowsArg($arg)
    {
        $arg = addcslashes($arg, '"');
        $arg = str_replace("(", "^(", $arg);
        $arg = str_replace(")", "^)", $arg);
        return $arg;
    }

    //@TODO must not send plain password through command line
    private function getCommand($panel_node_port, $client, $session_hash, $values, $debug)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $panel_node_port, $client, $session_hash, $values, $debug");
        switch ($this->getOS()) {
            case self::OS_WIN:
                return "start cmd /C \""
                    . "\"" . $this->escapeWindowsArg($this->testRunnerSettings["php_exec"]) . "\" "
                    . "\"" . $this->escapeWindowsArg($this->root) . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "console'\" concerto:r:start --env=" . $this->environment . " "
                    . "$panel_node_port "
                    . "$session_hash "
                    . "\"" . $this->escapeWindowsArg($client) . "\" "
                    . "$debug "
                    . "\"" . ($values ? $this->escapeWindowsArg($values) : "{}") . "\" "
                    . ">> "
                    . "\"" . $this->escapeWindowsArg($this->getOutputFilePath($session_hash)) . "\" "
                    . "2>&1\"";
            default:
                return "nohup "
                    . $this->testRunnerSettings["php_exec"] . " "
                    . "'" . $this->root . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "console' concerto:r:start --env=" . $this->environment . " "
                    . "$panel_node_port "
                    . "$session_hash "
                    . "'$client' "
                    . "$debug "
                    . ($values ? "'" . $values . "'" : "'{}'") . " "
                    . ">> "
                    . $this->getOutputFilePath($session_hash) . " "
                    . "2>&1 & echo $!";
        }
    }

    private function startProcess($panel_node_port, $client, $session_hash, $values, $debug)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $panel_node_port, $client, $session_hash, $values, $debug");
        $command = $this->getCommand($panel_node_port, $client, $session_hash, $values, $debug);
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