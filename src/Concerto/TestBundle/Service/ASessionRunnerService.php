<?php

namespace Concerto\TestBundle\Service;

use Concerto\PanelBundle\Entity\TestSession;
use Concerto\PanelBundle\Repository\TestSessionRepository;
use Concerto\PanelBundle\Service\AdministrationService;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Concerto\PanelBundle\Service\TestSessionService;

abstract class ASessionRunnerService
{
    const WRITER_TIMEOUT = 30;
    const OS_WIN = 0;
    const OS_LINUX = 1;

    protected $logger;
    protected $testRunnerSettings;
    protected $root;
    protected $doctrine;
    protected $testSessionCountService;
    protected $administrationService;
    protected $testSessionRepository;

    public function __construct(LoggerInterface $logger, $testRunnerSettings, $root, RegistryInterface $doctrine, TestSessionCountService $testSessionCountService, AdministrationService $administrationService, TestSessionRepository $testSessionRepository)
    {
        $this->logger = $logger;
        $this->testRunnerSettings = $testRunnerSettings;
        $this->root = $root;
        $this->doctrine = $doctrine;
        $this->testSessionCountService = $testSessionCountService;
        $this->administrationService = $administrationService;
        $this->testSessionRepository = $testSessionRepository;
    }

    abstract public function startNew(TestSession $session, $params, $client_ip, $client_browser, $debug = false, $max_exec_time = null);

    abstract public function submit(TestSession $session, $values, $client_ip, $client_browser);

    abstract public function backgroundWorker(TestSession $session, $values, $client_ip, $client_browser);

    abstract public function keepAlive(TestSession $session, $client_ip, $client_browser);

    abstract public function kill(TestSession $session, $client_ip, $client_browser);

    public function getConnection()
    {
        $con = $this->doctrine->getConnection($this->testRunnerSettings["connection"]);
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
        return $con_array;
    }

    public function getRDir()
    {
        return realpath($this->root . "/../src/Concerto/TestBundle/Resources/R") . "/";
    }

    public function getROutputFilePath($session_hash)
    {
        return realpath($this->root . "/../var/logs") . "/$session_hash.log";
    }

    public function getPublicDirPath()
    {
        return realpath($this->root . "/../src/Concerto/PanelBundle/Resources/public/files") . "/";
    }

    public function getMediaUrl()
    {
        return $this->testRunnerSettings["dir"] . "bundles/concertopanel/files/";
    }

    public function getWorkingDirPath($session_hash, $create = true)
    {
        $path = $this->root . "/../src/Concerto/TestBundle/Resources/sessions/$session_hash/";
        if ($create && !file_exists($path)) {
            mkdir($path, 0755, true);
        }
        return $path;
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

    public function escapeWindowsArg($arg)
    {
        $arg = addcslashes($arg, '"');
        $arg = str_replace("(", "^(", $arg);
        $arg = str_replace(")", "^)", $arg);
        return $arg;
    }

    public function getFifoDir()
    {
        return realpath($this->getRDir() . "fifo") . "/";
    }

    protected function checkSessionLimit($session, &$response)
    {
        $session_limit = $this->administrationService->getSessionLimit();
        $local_session_limit = $this->administrationService->getLocalSessionLimit();
        $total_limit_reached = $session_limit > 0 && $session_limit <= $this->testSessionCountService->getCurrentCount();
        $local_limit_reached = $local_session_limit > 0 && $local_session_limit <= $this->testSessionCountService->getCurrentLocalCount();
        if ($total_limit_reached || $local_limit_reached) {
            $session->setStatus(TestSessionService::STATUS_REJECTED);
            $this->testSessionRepository->save($session);
            $this->administrationService->insertSessionLimitMessage($session);
            $response = array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_SESSION_LIMIT_REACHED
            );
            return false;
        }
        return true;
    }

    protected function appendDebugDataToResponse(TestSession $session, $response, $offset = 0)
    {
        if (!$session->isDebug()) return $response;
        $out_path = $this->getROutputFilePath($session->getHash());
        if (file_exists($out_path)) {
            $new_data = file_get_contents($out_path, false, null, $offset);
            $response["debug"] = mb_convert_encoding($new_data, "UTF-8");
        }
        return $response;
    }

    protected function getDebugDataOffset(TestSession $session)
    {
        if (!$session->isDebug()) return 0;
        $out_path = $this->getROutputFilePath($session->getHash());
        if (file_exists($out_path)) {
            return filesize($out_path);
        }
        return 0;
    }

    protected function saveSubmitterPortFile($session_hash, $port)
    {
        $startTime = time();

        while (($fh = @fopen($this->getSubmitterPortFilePath($session_hash), "x")) === false) {
            if (time() - $startTime > self::WRITER_TIMEOUT) {
                $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - submitter port file timeout");
                return false;
            }
            usleep(100 * 1000);
        }
        fwrite($fh, $port . "\n");
        fclose($fh);
        return true;
    }

    protected function getSubmitterPortFilePath($session_hash)
    {
        return $this->getWorkingDirPath($session_hash) . "/submitter.port";
    }

    protected function isProcessReady($session_hash)
    {
        return !file_exists($this->getSubmitterPortFilePath($session_hash));
    }

    protected function waitForProcessReady($session_hash)
    {
        $startTime = time();
        while (!$this->isProcessReady($session_hash)) {
            if (time() - $startTime > self::WRITER_TIMEOUT) {
                return false;
            }
            usleep(100 * 1000);
        }
        return true;
    }

    protected function createSubmitterSock(TestSession $session, $save_file, &$submitter_sock, &$error_response)
    {
        $submitter_sock = $this->createListenerSocket();
        if ($submitter_sock === false) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - creating listener socket for submitter session failed");
            $error_response = array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_ERROR
            );
            return false;
        }

        socket_getsockname($submitter_sock, $submitter_ip, $submitter_port);

        if ($save_file) {
            if ($this->saveSubmitterPortFile($session->getHash(), $submitter_port) === false) {
                $error_response = array(
                    "source" => TestSessionService::SOURCE_TEST_NODE,
                    "code" => TestSessionService::RESPONSE_ERROR
                );
                return false;
            }
        }

        $session->setSubmitterPort($submitter_port);
        $this->testSessionRepository->save($session);
        return true;
    }

    protected function createListenerSocket($port = 0)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__);

        if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - socket_create() failed, listener socket, " . socket_strerror(socket_last_error()));
            return false;
        }
        if (socket_bind($sock, "0.0.0.0", $port) === false) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - socket_bind() failed, listener socket, " . socket_strerror(socket_last_error($sock)));
            socket_close($sock);
            return false;
        }
        if (socket_listen($sock, SOMAXCONN) === false) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - socket_listen() failed, listener socket, " . socket_strerror(socket_last_error($sock)));
            socket_close($sock);
            return false;
        }
        socket_set_nonblock($sock);
        return $sock;
    }

    protected function startListenerSocket($server_sock, $max_exec_time = NULL)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__);

        $startTime = time();
        $timeLimit = $max_exec_time;
        if ($timeLimit === null) {
            $timeLimit = $this->testRunnerSettings["max_execution_time"];
        }
        if($timeLimit > 0) {
            $timeLimit += 5;
        }
        do {
            if ($timeLimit > 0 && time() - $startTime > $timeLimit) {
                $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - start listener timeout");
                return false;
            }
            if (($client_sock = @socket_accept($server_sock)) === false) {
                continue;
            }

            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - socket accepted");

            if (false === ($buf = socket_read($client_sock, 8388608, PHP_NORMAL_READ))) {
                continue;
            }
            if (!$msg = trim($buf)) {
                continue;
            }

            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $msg");
            return $msg;
        } while (usleep(100 * 1000) || true);
    }

    protected function writeToProcess($submitter_sock, $response)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__);

        $startTime = time();
        do {
            if (($client_sock = socket_accept($submitter_sock)) === false) {
                if (time() - $startTime > self::WRITER_TIMEOUT) {
                    $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - writing to process timeout");
                    return false;
                }
                continue;
            }

            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - socket accepted");

            $buffer = json_encode($response) . "\n";
            $sent = socket_write($client_sock, $buffer);
            if ($sent === false) {
                $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - writing to process failed");
                return false;
            }
            if ($sent != strlen($buffer)) {
                $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - writing to process failed (length)");
                return false;
            }
            break;
        } while (usleep(100 * 1000) || true);
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - submitter ended");
        return true;
    }
}