<?php

namespace Concerto\PanelBundle\Command;

use Concerto\PanelBundle\Service\GitService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ConcertoGitDiffCommand extends Command
{
    const MAX_FILE_DIFF_LENGTH = 1000;
    const MAX_FILE_DIFF_NUM = 50;

    private $gitService;
    private $localGitRepoPath;
    private $sha;

    /** @var OutputInterface */
    private $output;

    public function __construct(GitService $gitService)
    {
        $this->gitService = $gitService;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("concerto:git:diff")->setDescription("Performs git diff");
        $this->addOption("sha", null, InputOption::VALUE_OPTIONAL, "Commit to get a diff for", null);
    }

    private function getDiffCommand()
    {
        $exec = $this->gitService->getGitExecPath();
        if ($this->sha) $shaCmd = $this->sha . "^ " . $this->sha;
        else $shaCmd = "HEAD";
        $options = ""; //"--ignore-cr-at-eol --ignore-all-space --ignore-blank-lines";
        return "i=-1; for f in `$exec diff $shaCmd $options --name-only`; do i=$((\$i+1)); if [ \"\$i\" -ge " . self::MAX_FILE_DIFF_NUM . " ]; then break; fi;  $exec diff $shaCmd $options -- \$f | head -n" . self::MAX_FILE_DIFF_LENGTH . "; done";
    }

    private function diff()
    {
        $command = $this->getDiffCommand();
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
        $this->sha = $input->getOption("sha");
        $this->localGitRepoPath = $this->gitService->getGitRepoPath();

        chdir($this->localGitRepoPath);

        if (!$this->gitService->isEnabled()) {
            $output->writeln("Git not initialized.");
            return 1;
        }
        if (!$this->diff()) return 1;
        return 0;
    }
}