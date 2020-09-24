<?php

namespace Concerto\PanelBundle\Command;

use Concerto\PanelBundle\Repository\ScheduledTaskRepository;
use Concerto\PanelBundle\Service\AdministrationService;
use Concerto\PanelBundle\Service\GitService;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Concerto\PanelBundle\Entity\ScheduledTask;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Templating\EngineInterface;

class ConcertoTaskGitUpdateCommand extends ConcertoScheduledTaskCommand
{
    private $templating;
    private $gitService;

    public function __construct(AdministrationService $administrationService, $administration, ManagerRegistry $doctrine, EngineInterface $templating, GitService $gitService, ScheduledTaskRepository $scheduledTaskRepository)
    {
        $this->templating = $templating;
        $this->gitService = $gitService;

        parent::__construct($administrationService, $administration, $doctrine, $scheduledTaskRepository);
    }

    protected function configure()
    {
        parent::configure();
        $this->setName("concerto:task:git:update")->setDescription("Updates git working copy");
        $this->getDefinition()->getOption("content-block")->setDefault(1);
        $this->addOption("instructions", "i", InputOption::VALUE_REQUIRED, "Import instructions", null);
    }

    public function getTaskDescription(ScheduledTask $task)
    {
        $info = json_decode($task->getInfo(), true);
        $desc = $this->templating->render("@ConcertoPanel/Administration/task_git_update.html.twig", array());
        return $desc;
    }

    public function getTaskInfo(InputInterface $input)
    {
        return array_merge(parent::getTaskInfo($input), [
            "instructions" => $input->getOption("instructions")
        ]);
    }

    public function getTaskType()
    {
        return ScheduledTask::TYPE_GIT_UPDATE;
    }

    protected function executeTask(ScheduledTask $task, OutputInterface $output)
    {
        $info = json_decode($task->getInfo(), true);
        $instructions = $info["instructions"];

        $result = $this->gitService->update($instructions, $updateOutput);
        $output->writeln($updateOutput);
        $this->gitService->setGitRepoOwner();
        return $result ? 0 : 1;
    }
}
