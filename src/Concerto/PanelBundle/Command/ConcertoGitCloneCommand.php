<?php

namespace Concerto\PanelBundle\Command;

use Concerto\PanelBundle\Service\GitService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class ConcertoGitCloneCommand extends Command
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
        $this->setName("concerto:git:clone")->setDescription("Clones git repository");
        $this->addOption("if-not-exists", null, InputOption::VALUE_NONE, "Clone only if Git repository not exists yet");
    }

    private function getCloneCommand()
    {
        $url = $this->gitService->getUrl();
        $branch = $this->gitService->getBranch();
        $login = $this->gitService->getLogin();
        $password = $this->gitService->getPassword();
        $exec = $this->gitService->getGitExecPath();

        $urlWithCreds = str_replace("://", "://$login:$password@", $url);

        return "$exec clone $urlWithCreds -b $branch " . $this->localGitRepoPath;
    }

    private function cleanUp()
    {
        $this->output->writeln("cleaning contents of " . $this->localGitRepoPath);
        $fs = new Filesystem();
        $rdi = new \RecursiveDirectoryIterator($this->localGitRepoPath, \FilesystemIterator::SKIP_DOTS);
        $fs->remove($rdi);
        $this->output->writeln("contents of " . $this->localGitRepoPath . " cleared successfully");
        return true;
    }

    private function clone()
    {
        $command = $this->getCloneCommand();
        $process = new Process($command);
        $process->start();
        $process->wait();

        if (!empty($process->getOutput())) $this->output->writeln($process->getOutput());
        if (!empty($process->getErrorOutput())) $this->output->writeln($process->getErrorOutput());
        return $process->getExitCode() === 0;
    }

    private function doesGitRepositoryExists()
    {
        $fs = new Filesystem();
        return $fs->exists($this->localGitRepoPath . "/.git");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->localGitRepoPath = $this->gitService->getGitRepoPath();
        $ifNotExists = $input->getOption("if-not-exists");

        if ($ifNotExists && $this->doesGitRepositoryExists()) {
            $output->writeln("Git repository already exists.");
            return 0;
        }

        if (!$this->cleanUp()) {
            return 1;
        }

        if (!$this->gitService->isEnabled()) {
            $output->writeln("Git not initialized.");
            return 1;
        }
        if (!$this->clone()) return 1;
        return 0;
    }
}