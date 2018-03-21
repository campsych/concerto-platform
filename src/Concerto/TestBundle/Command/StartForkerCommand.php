<?php

namespace Concerto\TestBundle\Command;

use Concerto\PanelBundle\Service\LoadBalancerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class StartForkerCommand extends Command
{

    private $testRunnerSettings;
    private $loadBalancerService;
    private $doctrine;

    public function __construct($testRunnerSettings, LoadBalancerInterface $loadBalancerService, RegistryInterface $doctrine)
    {
        parent::__construct();

        $this->doctrine = $doctrine;
        $this->testRunnerSettings = $testRunnerSettings;
        $this->loadBalancerService = $loadBalancerService;
    }

    protected function configure()
    {
        $this->setName("concerto:forker:start")->setDescription("Start forker process.");
    }

    private function getCommand($forkerPath, $logPath, $fifoPath, $publicDir, $mediaUrl, $dbDriver)
    {
        $cmd = "nohup " . $this->testRunnerSettings["rscript_exec"] . " --no-save --no-restore --quiet "
            . "'$forkerPath' "
            . "'$fifoPath' "
            . "'$publicDir' "
            . "'$mediaUrl' "
            . "'$dbDriver' "
            . ">> "
            . "'" . $logPath . "' "
            . "2>&1 & echo $!";
        return $cmd;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("starting forker...");
        $forkerPath = realpath(dirname(__FILE__) . "/../Resources/R/forker.R");
        $logPath = realpath(dirname(__FILE__) . "/../Resources/R") . "/forker.log";
        $fifoPath = realpath(dirname(__FILE__) . "/../Resources/R/fifo");
        $publicDir = realpath(dirname(__FILE__) . "/../../PanelBundle/Resources/public/files");

        $testNode = $this->loadBalancerService->getLocalTestNode();
        $panelNode = $testNode;
        $dbConnection = $this->doctrine->getConnection($panelNode["connection"]);
        $dbDriver = $dbConnection->getDriver()->getName();
        $mediaUrl = $testNode["dir"] . "bundles/concertopanel/files/";

        $cmd = $this->getCommand($forkerPath, $logPath, $fifoPath, $publicDir, $mediaUrl, $dbDriver);
        $process = new Process($cmd);
        $process->setEnhanceWindowsCompatibility(false);
        if ($this->testRunnerSettings["r_environ_path"] != null) {
            $env = array();
            $env["R_ENVIRON"] = $this->testRunnerSettings["r_environ_path"];
            $process->setEnv($env);
        }
        $process->mustRun();
        if ($process->getExitCode() == 0) {
            $output->writeln("forker started");
        } else {
            $output->writeln("forker started");
        }
    }

}
