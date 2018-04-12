<?php

namespace Concerto\TestBundle\Service;

use Concerto\PanelBundle\Entity\TestSession;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Process\Process;

class CheckpointSessionRunnerService extends ASessionRunnerService
{
    public function __construct(LoggerInterface $logger, $testRunnerSettings, $root, RegistryInterface $doctrine)
    {
        parent::__construct($logger, $testRunnerSettings, $root, $doctrine);
    }

    public function startNew(TestSession $session, $params, $client_ip, $client_browser, $debug = false)
    {
        $session_hash = $session->getHash();
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $params, $client_ip, $client_ip, $client_browser, $debug");

        $client = json_encode(array(
            "ip" => $client_ip,
            "browser" => $client_browser
        ));
        $response = $this->startCheckpointProcess($client, $session_hash, null);
        return $response;
    }

    public function submit(TestSession $session, $values, $client_ip, $client_browser, $time = null)
    {

    }

    public function backgroundWorker(TestSession $session, $values, $client_ip, $client_browser, $time = null)
    {

    }

    public function keepAlive(TestSession $session, $client_ip, $client_browser)
    {

    }

    public function kill(TestSession $session, $client_ip, $client_browser)
    {

    }

    private function getStartCheckpointCommand($client, $session_hash, $values)
    {
        $ini_path = $this->getRDir() . "/standalone.R";
        $max_exec_time = $this->testRunnerSettings["max_execution_time"];
        $rscript = $this->testRunnerSettings["rscript_exec"];
        $db_connection = $this->getSerializedConnection();
        $working_dir = $this->getWorkingDirPath($session_hash);
        $public_dir = $this->getPublicDirPath();
        $media_url = $this->getMediaUrl();
        $portFile = $working_dir . "coord_port";
        $rout_path = $this->getROutputFilePath($session_hash);
        $values = $values ? $values : "{}";

        return $this->testRunnerSettings["dmtcp_bin_path"] . "/dmtcp_launch "
            . "--new-coordinator "
            . "--coord-port 0 "
            . "--port-file '$portFile' "
            . "--ckptdir '$working_dir' "
            . $rscript . " --no-save --no-restore --quiet "
            . "'$ini_path' "
            . "'$db_connection' "
            . "'' " //test_node_port
            . "'' " //submitter
            . "'$client' "
            . "$session_hash "
            . "'$working_dir' "
            . "'$public_dir' "
            . "'$media_url' "
            . "$max_exec_time "
            . "'$values' "
            . ">> "
            . "'$rout_path' "
            . "2>&1 & echo $!";
    }

    private function startCheckpointProcess($client, $session_hash, $values)
    {
        $cmd = $this->getStartCheckpointCommand($client, $session_hash, $values);

        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $cmd");

        $process = new Process($cmd);
        if ($this->testRunnerSettings["r_environ_path"] != null) {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - setting process renviron to: " . $this->testRunnerSettings["r_environ_path"]);
            $env = array();
            $env["R_ENVIRON"] = $this->testRunnerSettings["r_environ_path"];
            $process->setEnv($env);
        }
        $process->mustRun();
        return true;
    }

    private function getRFifoPath($session_hash)
    {
        return $this->getWorkingDirPath($session_hash) . "/r.fifo";
    }

    private function getPhpFifoPath($session_hash)
    {
        return $this->getWorkingDirPath($session_hash) . "/php.fifo";
    }
}