<?php

namespace Concerto\PanelBundle\Command;

use Concerto\PanelBundle\Service\AdministrationService;
use Concerto\PanelBundle\Service\GitService;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Console\Input\InputInterface;
use Concerto\PanelBundle\Entity\ScheduledTask;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Templating\EngineInterface;

class ConcertoTaskGitEnableCommand extends ConcertoScheduledTaskCommand
{
    private $templating;
    private $gitService;
    private $localGitRepoPath;

    /** @var OutputInterface */
    private $output;

    public function __construct(AdministrationService $administrationService, $administration, ManagerRegistry $doctrine, EngineInterface $templating, GitService $gitService)
    {
        $this->templating = $templating;
        $this->gitService = $gitService;

        parent::__construct($administrationService, $administration, $doctrine);
    }

    protected function configure()
    {
        $this->setName("concerto:task:git:enable")->setDescription("Git enable");
        parent::configure();
    }

    public function getTaskDescription(ScheduledTask $task)
    {
        $info = json_decode($task->getInfo(), true);
        $desc = $this->templating->render("@ConcertoPanel/Administration/task_git_enable.html.twig", array(
        ));
        return $desc;
    }

    public function getTaskInfo(ScheduledTask $task, InputInterface $input)
    {
        $info = array_merge(parent::getTaskInfo($task, $input), array(
        ));
        return $info;
    }

    public function getTaskType()
    {
        return ScheduledTask::TYPE_GIT_ENABLE;
    }

    protected function executeTask(ScheduledTask $task, OutputInterface $output)
    {
        $this->output = $output;
        $this->localGitRepoPath = $this->gitService->getGitRepoPath();

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
}
