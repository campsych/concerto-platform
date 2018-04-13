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
    const LISTENER_TIMEOUT = 5;

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

        if (!$this->checkSessionLimit()) {
            $session->setStatus(TestSessionService::STATUS_REJECTED);
            $this->testSessionRepository->save($session);
            $this->administrationService->insertSessionLimitMessage($session);
            return array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_SESSION_LIMIT_REACHED
            );
        }

        $client = array(
            "ip" => $client_ip,
            "browser" => $client_browser
        );

        $submitter_sock = $this->createListenerSocket();
        if ($submitter_sock === false) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - creating listener socket for submitter session failed");
            return array(
                "source" => self::SOURCE_TEST_NODE,
                "code" => self::RESPONSE_ERROR
            );
        }

        socket_getsockname($submitter_sock, $submitter_ip, $submitter_port);
        $session->setSubmitterPort($submitter_port);
        $this->testSessionRepository->save($session);

        $success = false;
        if ($this->getOS() == self::OS_LINUX && $this->testRunnerSettings["session_forking"] == "true") {
            $success = $this->startChildProcess($client, $session_hash);
        } else {
            $success = $this->startStandaloneProcess($client, $session_hash);
        }
        if (!$success) {
            socket_close($submitter_sock);
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - creating R process failed");
            return array(
                "source" => self::SOURCE_TEST_NODE,
                "code" => self::RESPONSE_ERROR
            );
        }

        $response = json_decode($this->startListener($submitter_sock), true);
        if ($session->isDebug()) $response = $this->appendDebugDataToResponse($session_hash, $response);
        socket_close($submitter_sock);

        $this->testSessionRepository->clear();
        return $response;
    }

    public function submit(TestSession $session, $values, $client_ip, $client_browser)
    {
        $session_hash = $session->getHash();
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $values, $client_ip, $client_browser");

        $client = array(
            "ip" => $client_ip,
            "browser" => $client_browser
        );

        $submitter_sock = $this->createListenerSocket();
        if ($submitter_sock === false) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - creating listener socket for submitter session failed");
            return array(
                "source" => self::SOURCE_TEST_NODE,
                "code" => self::RESPONSE_ERROR
            );
        }
        socket_getsockname($submitter_sock, $submitter_ip, $submitter_port);

        $session->setSubmitterPort($submitter_port);
        $this->testSessionRepository->save($session);

        $this->savePhpListenerPortFile($session_hash, $submitter_port);
        $debugOffset = 0;
        if ($session->isDebug()) $debugOffset = $this->getDebugDataOffset($session_hash);
        $sent = $this->writeToProcess($submitter_sock, array(
            "source" => TestSessionService::SOURCE_PANEL_NODE,
            "code" => TestSessionService::RESPONSE_SUBMIT,
            "client" => $client,
            "values" => $values
        ));
        if ($sent === false) {
            socket_close($submitter_sock);
            return $response = array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_SESSION_LOST
            );
        }

        $response = json_decode($this->startListener($submitter_sock), true);
        if ($session->isDebug()) $response = $this->appendDebugDataToResponse($session_hash, $response, $debugOffset);
        socket_close($submitter_sock);

        $this->testSessionRepository->clear();
        return $response;
    }

    public function backgroundWorker(TestSession $session, $values, $client_ip, $client_browser)
    {
        $session_hash = $session->getHash();
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $values, $client_ip, $client_browser");

        $client = array(
            "ip" => $client_ip,
            "browser" => $client_browser
        );

        $submitter_sock = $this->createListenerSocket();
        if ($submitter_sock === false) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - creating listener socket for submitter session failed");
            return array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_ERROR
            );
        }
        socket_getsockname($submitter_sock, $submitter_ip, $submitter_port);

        $session->setSubmitterPort($submitter_port);
        $this->testSessionRepository->save($session);

        $this->savePhpListenerPortFile($session_hash, $submitter_port);
        $debugOffset = 0;
        if ($session->isDebug()) $debugOffset = $this->getDebugDataOffset($session_hash);

        $sent = $this->writeToProcess($submitter_sock, array(
            "source" => TestSessionService::SOURCE_PANEL_NODE,
            "code" => TestSessionService::RESPONSE_WORKER,
            "client" => $client,
            "values" => $values
        ));
        if ($sent === false) {
            socket_close($submitter_sock);
            return $response = array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_SESSION_LOST
            );
        }

        $response = json_decode($this->startListener($submitter_sock), true);
        if ($session->isDebug()) $response = $this->appendDebugDataToResponse($session_hash, $response, $debugOffset);
        socket_close($submitter_sock);

        $this->testSessionRepository->clear();
        return $response;
    }

    public function keepAlive(TestSession $session, $client_ip, $client_browser)
    {
        $session_hash = $session->getHash();
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $client_ip, $client_browser");

        $client = array(
            "ip" => $client_ip,
            "browser" => $client_browser
        );

        $submitter_sock = $this->createListenerSocket();
        if ($submitter_sock === false) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - creating listener socket for submitter session failed");
            return array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_ERROR
            );
        }
        socket_getsockname($submitter_sock, $submitter_ip, $submitter_port);

        $session->setSubmitterPort($submitter_port);
        $this->testSessionRepository->save($session);

        $this->savePhpListenerPortFile($session_hash, $submitter_port);

        $sent = $this->writeToProcess($submitter_sock, array(
            "source" => TestSessionService::SOURCE_PANEL_NODE,
            "code" => TestSessionService::RESPONSE_KEEPALIVE_CHECKIN,
            "client" => $client
        ));
        if ($sent === false) {
            socket_close($submitter_sock);
            return $response = array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_SESSION_LOST
            );
        }
        socket_close($submitter_sock);

        $this->testSessionRepository->clear();
        return array(
            "source" => TestSessionService::SOURCE_PROCESS,
            "code" => TestSessionService::RESPONSE_KEEPALIVE_CHECKIN
        );
    }

    public function kill(TestSession $session, $client_ip, $client_browser)
    {
        $session_hash = $session->getHash();
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $client_ip, $client_browser");

        $client = array(
            "ip" => $client_ip,
            "browser" => $client_browser
        );

        $submitter_sock = $this->createListenerSocket();
        if ($submitter_sock === false) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - creating listener socket for submitter session failed");
            return array(
                "source" => self::SOURCE_TEST_NODE,
                "code" => self::RESPONSE_ERROR
            );
        }
        socket_getsockname($submitter_sock, $submitter_ip, $submitter_port);

        $session->setSubmitterPort($submitter_port);
        $this->testSessionRepository->save($session);

        $this->savePhpListenerPortFile($session_hash, $submitter_port);

        $sent = $this->writeToProcess($submitter_sock, array(
            "source" => TestSessionService::SOURCE_PANEL_NODE,
            "code" => TestSessionService::RESPONSE_STOP,
            "client" => $client
        ));
        if ($sent === false) {
            socket_close($submitter_sock);
            return $response = array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_SESSION_LOST
            );
        }

        socket_close($submitter_sock);
        $this->testSessionRepository->clear();
        return array(
            "source" => TestSessionService::SOURCE_PROCESS,
            "code" => TestSessionService::RESPONSE_STOPPED
        );
    }

    private function getDebugDataOffset($session_hash)
    {
        $out_path = $this->getROutputFilePath($session_hash);
        if (file_exists($out_path)) {
            return filesize($out_path);
        }
        return 0;
    }

    private function appendDebugDataToResponse($session_hash, $response, $offset = 0)
    {
        $out_path = $this->getROutputFilePath($session_hash);
        if (file_exists($out_path)) {
            $new_data = file_get_contents($out_path, false, null, $offset);
            $response["debug"] = mb_convert_encoding($new_data, "UTF-8");
        }
        return $response;
    }

    private function savePhpListenerPortFile($session_hash, $port)
    {
        while (($fh = @fopen($this->getPhpListenerPortFilePath($session_hash), "x")) === false) {
            usleep(100 * 1000);
        }
        fwrite($fh, $port . "\n");
        fclose($fh);
    }

    private function getPhpListenerPortFilePath($session_hash)
    {
        return $this->getWorkingDirPath($session_hash) . "/php.port";
    }

    private function writeToProcess($submitter_sock, $response)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__);

        $startTime = time();
        do {
            if (($client_sock = socket_accept($submitter_sock)) === false) {
                if (time() - $startTime > self::LISTENER_TIMEOUT) return false;
                continue;
            }

            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - socket accepted");

            socket_write($client_sock, json_encode($response) . "\n");
            break;
        } while (usleep(100 * 1000) || true);
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - submitter ended");
        return true;
    }

    private function createListenerSocket($port = 0)
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

    private function startStandaloneProcess($client, $session_hash)
    {
        $cmd = $this->getCommand($client, $session_hash);
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $cmd");

        $process = new Process($cmd);
        $process->setEnhanceWindowsCompatibility(false);
        if ($this->testRunnerSettings["r_environ_path"] != null) {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - setting process renviron to: " . $this->testRunnerSettings["r_environ_path"]);
            $env = array();
            $env["R_ENVIRON"] = $this->testRunnerSettings["r_environ_path"];
            $process->setEnv($env);
        }
        $process->mustRun();
        return true;
    }

    private function startChildProcess($client, $session_hash)
    {
        $response = json_encode(array(
            "workingDir" => realpath($this->getWorkingDirPath($session_hash)) . DIRECTORY_SEPARATOR,
            "maxExecTime" => $this->testRunnerSettings["max_execution_time"],
            "maxIdleTime" => $this->testRunnerSettings["max_idle_time"],
            "keepAliveToleranceTime" => $this->testRunnerSettings["keep_alive_tolerance_time"],
            "client" => json_decode($client, true),
            "connection" => json_decode($this->getSerializedConnection(), true),
            "sessionId" => $session_hash,
            "rLogPath" => $this->getROutputFilePath($session_hash)
        ));

        $path = $this->getFifoDir() . "/" . $session_hash;
        posix_mkfifo($path, POSIX_S_IFIFO | 0644);
        $fh = fopen($path, "wt");
        if ($fh === false) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - fopen() failed");
            return false;
        }
        stream_set_blocking($fh, 1);
        $buffer = $response . "\n";
        $sent = fwrite($fh, $buffer);
        $success = $sent !== false;
        if (!$success) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - fwrite() failed");
        }
        if (strlen($buffer) != $sent) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - fwrite() failed, sent only $sent/" . strlen($buffer));
            $success = false;
        }
        fclose($fh);
        return $success;
    }

    private function checkSessionLimit()
    {
        $session_limit = $this->administrationService->getSessionLimit();
        $session_count = $this->testSessionCountService->getCurrentCount();
        if ($session_limit > 0 && $session_limit < $session_count + 1) {
            return false;
        }
        return true;
    }

    private function startListener($server_sock)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__);
        do {
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

    private function getCommand($client, $session_hash)
    {
        $rscript_exec = $this->testRunnerSettings["rscript_exec"];
        $ini_path = $this->getRDir() . "/standalone.R";
        $max_exec_time = $this->testRunnerSettings["max_execution_time"];
        $max_idle_time = $this->testRunnerSettings["max_idle_time"];
        $keep_alive_tolerance_time = $this->testRunnerSettings["keep_alive_tolerance_time"];
        $database_connection = $this->getSerializedConnection();
        $working_directory = $this->getWorkingDirPath($session_hash);
        $public_directory = $this->getPublicDirPath();
        $media_url = $this->getMediaUrl();
        $client = json_encode($client);

        switch ($this->getOS()) {
            case self::OS_LINUX:
                return "nohup " . $rscript_exec . " --no-save --no-restore --quiet "
                    . "'$ini_path' "
                    . "'$database_connection' "
                    . "'$client' "
                    . $session_hash . " "
                    . "'$working_directory' "
                    . "'$public_directory' "
                    . "'$media_url' "
                    . "$max_exec_time "
                    . "$max_idle_time "
                    . "$keep_alive_tolerance_time "
                    . ">> "
                    . "'" . $this->getOutputFilePath($session_hash) . "' "
                    . "> "
                    . "'" . $this->getROutputFilePath($session_hash) . "' "
                    . "2>&1 & echo $!";
            default:
                return "start cmd /C \""
                    . "\"" . $this->escapeWindowsArg($rscript_exec) . "\" --no-save --no-restore --quiet "
                    . "\"" . $this->escapeWindowsArg($ini_path) . "\" "
                    . "\"" . $this->escapeWindowsArg($database_connection) . "\" "
                    . "\"" . $this->escapeWindowsArg($client) . "\" "
                    . $session_hash . " "
                    . "\"" . $this->escapeWindowsArg($working_directory) . "\" "
                    . "\"" . $this->escapeWindowsArg($public_directory) . "\" "
                    . "$media_url "
                    . "$max_exec_time "
                    . "$max_idle_time "
                    . "$keep_alive_tolerance_time "
                    . ">> "
                    . "\"" . $this->escapeWindowsArg($this->getOutputFilePath($session_hash)) . "\" "
                    . "> "
                    . "\"" . $this->escapeWindowsArg($this->getROutputFilePath($session_hash)) . "\" "
                    . "2>&1\"";
        }
    }
}