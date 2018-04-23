<?php

namespace Concerto\TestBundle\Service;

use Concerto\PanelBundle\Entity\TestSession;
use Concerto\PanelBundle\Service\TestSessionService;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class CheckpointSessionRunnerService extends ASessionRunnerService
{
    const LOCK_TIMEOUT = 30;

    public function startNew(TestSession $session, $params, $client_ip, $client_browser, $debug = false)
    {
        $session_hash = $session->getHash();
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $params, $client_ip, $client_ip, $client_browser, $debug");

        if (!$this->checkSessionLimit($session, $response)) return $response;

        $client = json_encode(array(
            "ip" => $client_ip,
            "browser" => $client_browser
        ));

        $success = null;
        if ($this->isCheckpointedSessionInitOn()) {
            if (!$this->isInitSessionCheckpointReady()) {
                $success = $this->createInitSessionCheckpoint();
                if (!$success) {
                    $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - creating init checkpoint failed");
                    return array(
                        "source" => TestSessionService::SOURCE_TEST_NODE,
                        "code" => TestSessionService::RESPONSE_ERROR
                    );
                }
            }
            $success = $this->restoreInitProcess($session_hash);
        } else {
            $success = $this->startProcess($client, $session_hash);
        }
        if (!$success) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - starting session failed");
            return array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_ERROR
            );
        }

        if (!$this->createSubmitterSock($session, true, $submitter_sock, $error_response)) return $error_response;

        $sent = $this->writeToProcess($submitter_sock, array(
            "workingDir" => $this->getWorkingDirPath($session_hash),
            "client" => $client,
            "sessionHash" => $session_hash
        ));
        if ($sent === false) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - writing to process failed");
            socket_close($submitter_sock);
            return $response = array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_SESSION_LOST
            );
        }

        $response = json_decode($this->startListenerSocket($submitter_sock), true);
        $response = $this->appendDebugDataToResponse($session, $response);
        $this->testSessionRepository->clear();

        $success = $this->saveProcess($session_hash);
        if (!$success) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - saving process failed");
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
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - submit lock timeout");
            return array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_ERROR
            );
        }
        if (!$this->createSubmitterSock($session, true, $submitter_sock, $error_response)) return $error_response;

        $success = $this->restoreProcess($session_hash);
        if (!$success) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - restoring process failed");
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
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - writing to process failed");
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
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - saving process failed");
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
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - background worker lock timeout");
            return array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_ERROR
            );
        }
        if (!$this->createSubmitterSock($session, true, $submitter_sock, $error_response)) return $error_response;
        $success = $this->restoreProcess($session_hash);
        if (!$success) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - restoring process failed");
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
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - writing to process failed");
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
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - saving process failed");
            return array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_ERROR
            );
        }

        return $response;
    }

    public function keepAlive(TestSession $session, $client_ip, $client_browser)
    {
        return array(
            "source" => TestSessionService::SOURCE_PROCESS,
            "code" => TestSessionService::RESPONSE_KEEPALIVE_CHECKIN
        );
    }

    public function kill(TestSession $session, $client_ip, $client_browser)
    {
        return array(
            "source" => TestSessionService::SOURCE_PROCESS,
            "code" => TestSessionService::RESPONSE_STOPPED
        );
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

    private function getInitCheckpointFilePath()
    {
        return $this->getInitCheckpointDirPath() . "/ckpt_*.dmtcp";
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
            if (time() - $startTime > self::LOCK_TIMEOUT) return false;
        }
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

    private function restoreInitProcess($session_hash)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__);
        $cmd = $this->getRestoreInitProcessCommand($session_hash);
        $this->logger->info($cmd);
        $process = new Process($cmd);
        $process->setWorkingDirectory($this->getWorkingDirPath($session_hash));
        $process->start();
        return true;
    }

    private function isCheckpointedSessionInitOn()
    {
        return $this->testRunnerSettings["checkpointed_session_init"] == "true";
    }

    private function isInitSessionCheckpointReady()
    {
        return count(glob($this->getInitCheckpointDirPath() . "/ckpt_*.dmtcp")) > 0;
    }

    private function createInitSessionCheckpoint()
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__);
        $workingDir = $this->getInitCheckpointDirPath();
        $renviron = $this->testRunnerSettings["r_environ_path"];

        $this->cleaningPreviousCheckpoints();
        touch($this->getInitCheckpointLockPath());
        $exitCode = $this->startInitCheckpointProcess($workingDir, $renviron);
        if ($exitCode != 0) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - something went wrong when starting initialization checkpoint process: non zero exit code");
            return false;
        }

        if (!$this->waitForUnlockedInitCheckpoint()) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - init checkpoint lock timeout #1");
            return false;
        }

        $exitCode = $this->checkpointInitProcess();
        if ($exitCode != 0) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - something went wrong with checkpointing: non zero exit code");
            return false;
        }
        return true;
    }

    private function cleaningPreviousCheckpoints()
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__);
        $fs = new Filesystem();
        foreach (glob($this->getInitCheckpointDirPath() . "/*") as $content) {
            @$fs->remove($content);
        }
    }

    private function startInitCheckpointProcess($workingDir, $renviron)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__);
        $cmd = $this->getInitCheckpointProcessCommand($workingDir);
        $this->logger->info($cmd);
        $process = new Process($cmd);
        $process->setWorkingDirectory($workingDir);
        if ($renviron != null) {
            $env = array();
            $env["R_ENVIRON"] = $renviron;
            $process->setEnv($env);
        }
        $process->start();
        return 0;
    }

    private function getInitCheckpointPortPath()
    {
        return $this->getInitCheckpointDirPath() . "/init.port";
    }

    private function waitForUnlockedInitCheckpoint()
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__);
        $startTime = time();
        while (file_exists($this->getInitCheckpointLockPath())) {
            usleep(100 * 1000);
            if (time() - $startTime > self::LOCK_TIMEOUT) {
                return false;
            }
        }
        return true;
    }

    private function getInitCheckpointLockPath()
    {
        return $this->getInitCheckpointDirPath() . "/checkpoint.lock";
    }

    private function readCheckpointInitPort()
    {
        return file_get_contents($this->getInitCheckpointPortPath());
    }

    public function getInitCheckpointDirPath()
    {
        return $this->getRDir() . "/init_checkpoint";
    }

    private function getCheckpointInitLogPath()
    {
        return $this->getInitCheckpointDirPath() . "/init.log";
    }

    private function getInitCheckpointProcessCommand($workingDir)
    {
        $dmtcpBinPath = $this->testRunnerSettings["dmtcp_bin_path"];
        $checkpointPath = realpath(dirname(__FILE__) . "/../Resources/R/checkpoint.R");
        $publicDir = realpath(dirname(__FILE__) . "/../../PanelBundle/Resources/public/files");
        $connection = $this->getSerializedConnection();
        $mediaUrl = $this->testRunnerSettings["dir"] . "bundles/concertopanel/files/";
        $maxExecTime = $this->testRunnerSettings["max_execution_time"];
        $rscriptBinPath = $this->testRunnerSettings["rscript_exec"];
        $portFile = $this->getInitCheckpointPortPath();
        $coordLog = $this->getInitCheckpointDirPath() . "/coord.log";

        return "exec $dmtcpBinPath/dmtcp_launch "
            . "--new-coordinator "
            . "--coord-port 0 "
            . "--port-file '$portFile' "
            . "--ckptdir '$workingDir' "
            //. "--coord-logfile '$coordLog' "
            //. "--checkpoint-open-files "
            //. "--allow-file-overwrite "
            //. "--disable-all-plugins "
            . "-i 0 "
            . "--no-gzip "
            . "$rscriptBinPath --no-save --no-restore --quiet "
            . "'$checkpointPath' "
            . "'$publicDir' "
            . "'$mediaUrl' "
            . "'$connection' "
            . "$maxExecTime "
            . "> /dev/null "
            . "2>&1 4<&- 5<&- 6<&- 7<&- 8<&- 9<&- &";
    }

    private function checkpointInitProcess()
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__);

        $dmtcpBinPath = $this->testRunnerSettings["dmtcp_bin_path"];
        $port = $this->readCheckpointInitPort();

        $cmd = "$dmtcpBinPath/dmtcp_command -bc -p $port > /dev/null 2>&1";
        $this->logger->info($cmd);
        $process = new Process($cmd);
        $process->run();
        if ($process->getExitCode() !== 0) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - checkpointing init process failed");
            return $process->getExitCode();
        }
        $this->logger->info("init process checkpointed");

        $cmd = "$dmtcpBinPath/dmtcp_command -q -p $port > /dev/null 2>&1";
        $this->logger->info($cmd);
        $process = new Process($cmd);
        $process->run();
        if ($process->getExitCode() !== 0) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - closing init process failed");
            return $process->getExitCode();
        }
        $this->logger->info("init process closed");

        return 0;
    }

    private function getRestoreInitProcessCommand($session_hash)
    {
        $portFile = $this->getCoordinatorPortPath($session_hash);
        $dmtcp_path = $this->getInitCheckpointFilePath();
        $out = $this->getROutputFilePath($session_hash);
        $working_dir = $this->getWorkingDirPath($session_hash);
        $coord_log_path = $this->getCoordinatorLogFilePath($session_hash);

        return $this->testRunnerSettings["dmtcp_bin_path"] . "/dmtcp_restart "
            . "--new-coordinator "
            . "--coord-port 0 "
            . "--port-file '$portFile' "
            . "--ckptdir '$working_dir' "
            //. "--coord-logfile '$coord_log_path' "
            . "-i 0 "
            . "--no-strict-checking "
            . "$dmtcp_path "
            //. ">> '$out' "
            . "> /dev/null "
            . "2>&1 4<&- 5<&- 6<&- 7<&- 8<&- 9<&-";
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
            //. "--coord-logfile '$coord_log_path' "
            //. "--checkpoint-open-files "
            //. "--allow-file-overwrite "
            //. "--disable-all-plugins "
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
            //. "--coord-logfile '$coord_log_path' "
            . "-i 0 "
            . "--no-strict-checking "
            . "$dmtcp_path "
            //. ">> '$out' "
            . "> /dev/null "
            . "2>&1 4<&- 5<&- 6<&- 7<&- 8<&- 9<&-";
    }

    private function saveProcess($session_hash)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__);

        /*
        $dmtcpBinPath = $this->testRunnerSettings["dmtcp_bin_path"];
        $port = $this->getCoordinatorPort($session_hash);

        $cmd = "$dmtcpBinPath/dmtcp_command -bc -p $port > /dev/null 2>&1";
        $this->logger->info($cmd);
        $process = new Process($cmd);
        $process->run();
        if ($process->getExitCode() !== 0) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - checkpointing process failed");
            return false;
        }
        $this->logger->info("process checkpointed");

        $cmd = "$dmtcpBinPath/dmtcp_command -q -p $port > /dev/null 2>&1";
        $this->logger->info($cmd);
        $process = new Process($cmd);
        $process->run();
        if ($process->getExitCode() !== 0) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - closing process failed");
            return false;
        }
        $this->logger->info("process closed");

        return true;
        */

        touch($this->getCheckpointLockFilePath($session_hash));
        $port = $this->getCoordinatorPort($session_hash);
        $cmd = "nohup sh -c '"
            . $this->testRunnerSettings["dmtcp_bin_path"] . "/dmtcp_command -bc -p $port "
            . "&& "
            . $this->testRunnerSettings["dmtcp_bin_path"] . "/dmtcp_command -q -p $port "
            . "&& "
            . "rm " . $this->getCheckpointLockFilePath($session_hash)
            . "' > /dev/null 2>&1 &";
        $process = new Process($cmd);
        $process->mustRun();
        return true;
    }
}