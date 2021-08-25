<?php

namespace Concerto\PanelBundle\Command;

use Concerto\PanelBundle\Service\GitService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ConcertoGitCommitCommand extends Command
{
    private $gitService;
    private $localGitRepoPath;
    private $username;
    private $email;
    private $message;

    /** @var OutputInterface */
    private $output;

    public function __construct(GitService $gitService)
    {
        $this->gitService = $gitService;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("concerto:git:commit")->setDescription("Performs git commit");
        $this->addArgument("message", InputArgument::REQUIRED, "Commit message");
        $this->addArgument("username", InputArgument::REQUIRED, "Commit username");
        $this->addArgument("email", InputArgument::REQUIRED, "Commit email");
    }

    private function getCommitCommand()
    {
        $exec = $this->gitService->getGitExecPath();
        $usernameCmd = "$exec config user.name \"" . $this->username . "\"";
        $emailCmd = "$exec config user.email \"" . $this->email . "\"";
        $msgCmd = $this->message ? "-m\"" . $this->message . "\"" : "";
        return "$usernameCmd; $emailCmd; $exec commit $msgCmd";
    }

    private function commit()
    {
        $command = $this->getCommitCommand();
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
        $this->username = $input->getArgument("username");
        $this->email = $input->getArgument("email");
        $this->message = $input->getArgument("message");

        chdir($this->localGitRepoPath);

        if (!$this->gitService->isEnabled()) {
            $output->writeln("Git not initialized.");
            return 1;
        }
        if (!$this->commit()) return 1;
        return 0;
    }
}