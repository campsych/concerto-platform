<?php

namespace Concerto\PanelBundle\Command;

use Concerto\PanelBundle\Service\GitService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ConcertoGitPositionCommand extends Command
{
    private $gitService;
    private $localGitRepoPath;
    private $direction;

    /** @var OutputInterface */
    private $output;

    public function __construct(GitService $gitService)
    {
        $this->gitService = $gitService;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("concerto:git:position")->setDescription("Returns how many commits ahead or behind is local branch");
        $this->addArgument("direction", InputArgument::OPTIONAL, "One of the following: behind, ahead.", "behind");
    }

    private function getPositionCommand()
    {
        $branch = $this->gitService->getBranch();
        $exec = $this->gitService->getGitExecPath();
        $pos = $this->direction == "behind" ? "left" : "right";

        return "$exec rev-list --$pos-only --count origin/$branch...$branch";
    }

    private function position()
    {
        $command = $this->getPositionCommand();
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
        $this->direction = $input->getArgument("direction");

        chdir($this->localGitRepoPath);

        if (!$this->gitService->isEnabled()) {
            $output->writeln("Git not initialized.");
            return 1;
        }
        if (!$this->position()) return 1;
        return 0;
    }
}