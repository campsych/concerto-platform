<?php

namespace Concerto\TestBundle\Service;

use Concerto\PanelBundle\Entity\TestSession;
use Concerto\PanelBundle\Service\TestSessionService;
use Symfony\Component\Process\Process;

class CheckpointSessionRunnerService extends ASessionRunnerService
{

    public function startNew(TestSession $session, $params, $client_ip, $client_browser, $debug = false)
    {
        $session_hash = $session->getHash();
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $params, $client_ip, $client_ip, $client_browser, $debug");

        if (!$this->checkSessionLimit($session, $response)) return $response;

        $client = json_encode(array(
            "ip" => $client_ip,
            "browser" => $client_browser
        ));

        if (!$this->createSubmitterSock($session, false, $submitter_sock, $error_response)) return $error_response;

        $success = $this->startProcess($client, $session_hash);
        if (!$success) {
            return array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_ERROR
            );
        }

        $response = json_decode($this->startListenerSocket($submitter_sock), true);
        $response = $this->appendDebugDataToResponse($session, $response);
        $this->testSessionRepository->clear();

        $success = $this->saveProcess($session_hash);
        if (!$success) {
            return array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_ERROR
            );
        }

        return $response;
    }

    public function submit(TestSession $session, $values, $client_ip, $client_browser, $time = null)
    {
        $session_hash = $session->getHash();
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $values, $client_ip, $client_browser");

        $client = array(
            "ip" => $client_ip,
            "browser" => $client_browser
        );

        if (!$this->waitForUnlockedCheckpoint($session_hash)) {
            return array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_ERROR
            );
        }
        if (!$this->createSubmitterSock($session, true, $submitter_sock, $error_response)) return $error_response;
        $success = $this->restoreProcess($session_hash);
        if (!$success) {
            socket_close($submitter_sock);
            return array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_ERROR
            );
        }

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

        $response = json_decode($this->startListenerSocket($submitter_sock), true);
        $response = $this->appendDebugDataToResponse($session, $response, $debugOffset);
        socket_close($submitter_sock);
        $this->testSessionRepository->clear();

        $success = $this->saveProcess($session_hash);
        if (!$success) {
            return array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_ERROR
            );
        }

        return $response;
    }

    public function backgroundWorker(TestSession $session, $values, $client_ip, $client_browser, $time = null)
    {
        $session_hash = $session->getHash();
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $values, $client_ip, $client_browser");

        $client = array(
            "ip" => $client_ip,
            "browser" => $client_browser
        );

        if (!$this->waitForUnlockedCheckpoint($session_hash)) {
            return array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_ERROR
            );
        }
        if (!$this->createSubmitterSock($session, true, $submitter_sock, $error_response)) return $error_response;
        $success = $this->restoreProcess($session_hash);
        if (!$success) {
            socket_close($submitter_sock);
            return array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_ERROR
            );
        }

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

        $response = json_decode($this->startListenerSocket($submitter_sock), true);
        $response = $this->appendDebugDataToResponse($session, $response, $debugOffset);
        socket_close($submitter_sock);
        $this->testSessionRepository->clear();

        $success = $this->saveProcess($session_hash);
        if (!$success) {
            return array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_ERROR
            );
        }

        return $response;
    }

    public function keepAlive(TestSession $session, $client_ip, $client_browser)
    {

    }

    public function kill(TestSession $session, $client_ip, $client_browser)
    {

    }

    private function getStartProcessCommand($client, $session_hash)
    {
        $ini_path = $this->getRDir() . "/standalone.R";
        $max_exec_time = $this->testRunnerSettings["max_execution_time"];
        $rscript = $this->testRunnerSettings["rscript_exec"];
        $db_connection = $this->getSerializedConnection();
        $working_dir = $this->getWorkingDirPath($session_hash);
        $public_dir = $this->getPublicDirPath();
        $media_url = $this->getMediaUrl();
        $portFile = $this->getCoordinatorPortPath($session_hash);
        $rout_path = $this->getROutputFilePath($session_hash);
        $coord_log_path = $this->getCoordinatorLogFilePath($session_hash);

        return "nohup " . $this->testRunnerSettings["dmtcp_bin_path"] . "/dmtcp_launch "
            . "--new-coordinator "
            . "--coord-port 0 "
            . "--port-file '$portFile' "
            . "--ckptdir '$working_dir' "
            . "--coord-logfile '$coord_log_path' "
            . "-i 0 "
            . "--no-gzip "
            . $rscript . " --no-save --no-restore --quiet "
            . "'$ini_path' "
            . "'$db_connection' "
            . "'$client' "
            . "$session_hash "
            . "'$working_dir' "
            . "'$public_dir' "
            . "'$media_url' "
            . "$max_exec_time "
            . "0 " //max_idle_time
            . "0 " //keep_alive_tolerance_time
            . ">> "
            . "'$rout_path' "
            . "2>&1 & echo $!";
    }

    private function getRestoreProcessCommand($session_hash)
    {
        $portFile = $this->getCoordinatorPortPath($session_hash);
        $dmtcp_path = $this->getCheckpointFilePath($session_hash);
        $out = $this->getROutputFilePath($session_hash);
        $working_dir = $this->getWorkingDirPath($session_hash);
        $coord_log_path = $this->getCoordinatorLogFilePath($session_hash);

        return $this->testRunnerSettings["dmtcp_bin_path"] . "/dmtcp_restart "
            . "--new-coordinator "
            . "--coord-port 0 "
            . "--port-file '$portFile' "
            . "--ckptdir '$working_dir' "
            . "--coord-logfile '$coord_log_path' "
            . "-i 0 "
            . "--no-strict-checking "
            . "$dmtcp_path "
            . ">> '$out' "
            . "2>&1 & echo $!";
    }

    private function startProcess($client, $session_hash)
    {
        $cmd = $this->getStartProcessCommand($client, $session_hash);

        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $cmd");

        $process = new Process($cmd);
        if ($this->testRunnerSettings["r_environ_path"] != null) {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - setting process renviron to: " . $this->testRunnerSettings["r_environ_path"]);
            $env = array();
            $env["R_ENVIRON"] = $this->testRunnerSettings["r_environ_path"];
            $process->setEnv($env);
        }
        $process->mustRun();
        return $process->getExitCode() === 0;
    }

    private function getCoordinatorPortPath($session_hash)
    {
        return $this->getWorkingDirPath($session_hash) . "coord.port";
    }

    private function getCoordinatorPort($session_hash)
    {
        return file_get_contents($this->getCoordinatorPortPath($session_hash));
    }

    private function getCheckpointFilePath($session_hash)
    {
        return $this->getWorkingDirPath($session_hash) . "ckpt_*.dmtcp";
    }

    private function getCheckpointLockFilePath($session_hash)
    {
        return $this->getWorkingDirPath($session_hash) . "ckpt.lock";
    }

    private function getCoordinatorLogFilePath($session_hash)
    {
        return $this->getWorkingDirPath($session_hash) . "coord.log";
    }

    private function waitForUnlockedCheckpoint($session_hash)
    {
        $startTime = time();
        while (file_exists($this->getCheckpointLockFilePath($session_hash))) {
            usleep(100 * 1000);
            if (time() - $startTime > self::LISTENER_TIMEOUT) return false;
        }
        return true;
    }

    private function saveProcess($session_hash)
    {
        touch($this->getCheckpointLockFilePath($session_hash));
        $port = $this->getCoordinatorPort($session_hash);
        $cmd = "nohup sh -c '"
            . $this->testRunnerSettings["dmtcp_bin_path"] . "/dmtcp_command -bc -p $port "
            . "&& "
            . $this->testRunnerSettings["dmtcp_bin_path"] . "/dmtcp_command -q -p $port "
            . "&& "
            . "rm " . $this->getCheckpointLockFilePath($session_hash)
            . "' &";
        $process = new Process($cmd);
        $process->start();
        return true;
    }

    private function restoreProcess($session_hash)
    {
        $cmd = $this->getRestoreProcessCommand($session_hash);
        $process = new Process($cmd);
        $process->setWorkingDirectory($this->getWorkingDirPath($session_hash));
        $process->start();
        return true;
    }
}