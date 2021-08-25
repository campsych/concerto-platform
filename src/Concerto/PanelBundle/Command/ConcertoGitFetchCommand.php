<?php

namespace Concerto\PanelBundle\Command;

use Concerto\PanelBundle\Service\GitService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ConcertoGitFetchCommand extends Command
{
    private $gitService;
    private $localGitRepoPath;

    /** @var OutputInterface */
    private $output;

    public function __construct(GitService $gitService)
    {
        $this->gitService = $gitService;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("concerto:git:fetch")->setDescription("Performs git fetch");
    }

    private function getFetchCommand()
    {
        $exec = $this->gitService->getGitExecPath();
        return "$exec fetch";
    }

    private function fetch()
    {
        $command = $this->getFetchCommand();
        $process = new Process($command);
        $process->setTimeout(null);
        $process->start();
        $process->wait();

        if (!empty($process->getOutput())) $this->output->writeln($process->getOutput());
        if (!empty($process->getErrorOutput())) $this->output->writeln($process->getErrorOutput());
        return $process->getExitCode() === 0;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->localGitRepoPath = $this->gitService->getGitRepoPath();

        chdir($this->localGitRepoPath);

        if (!$this->gitService->isEnabled()) {
            $output->writeln("Git not initialized.");
            return 1;
        }
        if (!$this->fetch()) return 1;
        return 0;
    }
}