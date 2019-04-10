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
    private $environment;

    public function __construct(LoggerInterface $logger, TestSessionRepository $testSessionRepository, AdministrationService $administrationService, TestSessionCountService $testSessionCountService, RegistryInterface $doctrine, $testRunnerSettings, $root, $environment)
    {
        parent::__construct($logger, $testRunnerSettings, $root, $doctrine, $testSessionCountService, $administrationService, $testSessionRepository);

        $this->environment = $environment;
    }

    public function startNew(TestSession $session, $params, $client_ip, $client_browser, $debug = false, $max_exec_time = null)
    {
        $session_hash = $session->getHash();
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $params, $client_ip, $client_ip, $client_browser, $debug");

        if (!$this->checkSessionLimit($session, $response)) return $response;

        $client = array(
            "ip" => $client_ip,
            "browser" => $client_browser
        );

        if (!$this->createSubmitterSock($session, false, $submitter_sock, $error_response)) return $error_response;

        $success = false;
        if ($this->getOS() == self::OS_LINUX && $this->testRunnerSettings["session_forking"] == "true") {
            $success = $this->startChildProcess($client, $session_hash, $max_exec_time);
        } else {
            $success = $this->startStandaloneProcess($client, $session_hash, $max_exec_time);
        }
        if (!$success) {
            socket_close($submitter_sock);
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - creating R process failed");
            return array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_ERROR
            );
        }

        $response = $this->startListenerSocket($submitter_sock, $max_exec_time);
        if ($response === false) {
            return array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_ERROR
            );
        }
        $response = json_decode($response, true);
        $response = $this->appendDebugDataToResponse($session, $response);
        socket_close($submitter_sock);

        $this->testSessionRepository->clear();
        return $response;
    }

    public function submit(TestSession $session, $values, $client_ip, $client_browser)
    {
        $session_hash = $session->getHash();
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $client_ip, $client_browser");

        $client = array(
            "ip" => $client_ip,
            "browser" => $client_browser
        );

        if (!$this->createSubmitterSock($session, true, $submitter_sock, $error_response)) return $error_response;
        $debugOffset = $this->getDebugDataOffset($session);

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

        $response = $this->startListenerSocket($submitter_sock);
        if ($response === false) {
            return array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_ERROR
            );
        }
        $response = json_decode($response, true);
        $response = $this->appendDebugDataToResponse($session, $response, $debugOffset);
        socket_close($submitter_sock);

        $this->testSessionRepository->clear();
        return $response;
    }

    public function backgroundWorker(TestSession $session, $values, $client_ip, $client_browser)
    {
        $session_hash = $session->getHash();
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $client_ip, $client_browser");

        $client = array(
            "ip" => $client_ip,
            "browser" => $client_browser
        );

        if (!$this->createSubmitterSock($session, true, $submitter_sock, $error_response)) return $error_response;
        $debugOffset = $this->getDebugDataOffset($session);

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

        $response = $this->startListenerSocket($submitter_sock);
        if ($response === false) {
            return array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_ERROR
            );
        }
        $response = json_decode($response, true);
        $response = $this->appendDebugDataToResponse($session, $response, $debugOffset);
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

        if (!$this->createSubmitterSock($session, true, $submitter_sock, $error_response)) return $error_response;

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

        if (!$this->createSubmitterSock($session, true, $submitter_sock, $error_response)) return $error_response;

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

    private function startStandaloneProcess($client, $session_hash, $max_exec_time)
    {
        $cmd = $this->getCommand($client, $session_hash, $max_exec_time);
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $cmd");

        $process = new Process($cmd);
        $process->setEnhanceWindowsCompatibility(false);

        $env = array(
            "R_GC_MEM_GROW" => 0
        );
        if ($this->testRunnerSettings["r_environ_path"] != null) {
            $env["R_ENVIRON"] = $this->testRunnerSettings["r_environ_path"];
        }
        $process->setEnv($env);
        $process->mustRun();
        return true;
    }

    private function startChildProcess($client, $session_hash, $max_exec_time)
    {
        if ($max_exec_time === null) {
            $max_exec_time = $this->testRunnerSettings["max_execution_time"];
        }

        $response = json_encode(array(
            "workingDir" => realpath($this->getWorkingDirPath($session_hash)) . DIRECTORY_SEPARATOR,
            "maxExecTime" => $max_exec_time,
            "maxIdleTime" => $this->administrationService->getSettingValueForSessionHash($session_hash, "max_idle_time"),
            "keepAliveToleranceTime" => $this->testRunnerSettings["keep_alive_tolerance_time"],
            "client" => $client,
            "connection" => $this->getConnection(),
            "sessionId" => $session_hash,
            "rLogPath" => $this->getROutputFilePath($session_hash)
        ));

        $path = $this->getFifoDir() . $session_hash . ".fifo";
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

    private function getCommand($client, $session_hash, $max_exec_time)
    {
        $rscript_exec = $this->testRunnerSettings["rscript_exec"];
        $ini_path = $this->getRDir() . "standalone.R";
        if ($max_exec_time === null) {
            $max_exec_time = $this->testRunnerSettings["max_execution_time"];
        }
        $max_idle_time = $this->administrationService->getSettingValueForSessionHash($session_hash, "max_idle_time");
        $keep_alive_tolerance_time = $this->testRunnerSettings["keep_alive_tolerance_time"];
        $database_connection = json_encode($this->getConnection());
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
                    . "> "
                    . "\"" . $this->escapeWindowsArg($this->getROutputFilePath($session_hash)) . "\" "
                    . "2>&1\"";
        }
    }
}