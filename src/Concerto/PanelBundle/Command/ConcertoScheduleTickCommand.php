<?php

namespace Concerto\PanelBundle\Command;

use Concerto\PanelBundle\Repository\ScheduledTaskRepository;
use Concerto\PanelBundle\Service\AdministrationService;
use Concerto\PanelBundle\Service\GitService;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Concerto\PanelBundle\Entity\ScheduledTask;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Templating\EngineInterface;

class ConcertoScheduleTickCommand extends Command
{
    private $administrationService;
    private $templating;
    private $doctrine;
    private $gitService;
    private $scheduledTasksRepository;

    private $tasksDefinition = [
        ScheduledTask::TYPE_R_PACKAGE_INSTALL => [
            "name" => "concerto:task:package:install"
        ],
        ScheduledTask::TYPE_GIT_PULL => [
            "name" => "concerto:task:git:pull"
        ],
        ScheduledTask::TYPE_GIT_ENABLE => [
            "name" => "concerto:task:git:enable"
        ],
        ScheduledTask::TYPE_GIT_UPDATE => [
            "name" => "concerto:task:git:update"
        ],
        ScheduledTask::TYPE_GIT_RESET => [
            "name" => "concerto:task:git:reset"
        ],
        ScheduledTask::TYPE_CONTENT_IMPORT => [
            "name" => "concerto:task:content:import"
        ]
    ];

    public function __construct(ManagerRegistry $doctrine, AdministrationService $administrationService, EngineInterface $templating, GitService $gitService, ScheduledTaskRepository $scheduledTaskRepository)
    {
        $this->administrationService = $administrationService;
        $this->templating = $templating;
        $this->doctrine = $doctrine;
        $this->gitService = $gitService;
        $this->scheduledTasksRepository = $scheduledTaskRepository;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("concerto:schedule:tick")->setDescription("Administrative tasks tick.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->scheduledTasksRepository->findAllPending() as $task) {
            $this->executeTask($task, $output);
        }
        return 0;
    }

    private function executeTask(ScheduledTask $task, OutputInterface $output)
    {
        $def = $this->tasksDefinition[$task->getType()];

        $app = $this->getApplication()->find($def["name"]);
        $input = new ArrayInput([
            "command" => $def["name"],
            "--task" => $task->getId()
        ]);
        $bo = new BufferedOutput();
        $return_code = $app->run($input, $bo);
        $response = $bo->fetch();

        $this->scheduledTasksRepository->refresh($task);
        $task->appendOutput($response);
        $output->writeln($response);
        $this->scheduledTasksRepository->save($task);

        if ($this->administrationService->isContentBlocked() && !$this->isOngoingBlockingTaskExist()) {
            $this->administrationService->setContentBlock(false);
        }

        return $return_code;
    }

    private function isOngoingBlockingTaskExist()
    {
        foreach ($this->scheduledTasksRepository->findAllPending() as $task) {
            $info = json_decode($task->getInfo(), true);
            $blockingTask = isset($info["content_block"]) && $info["content_block"] === 1;
            if ($blockingTask) return true;
        }
        foreach ($this->scheduledTasksRepository->findAllOngoing() as $task) {
            $info = json_decode($task->getInfo(), true);
            $blockingTask = isset($info["content_block"]) && $info["content_block"] === 1;
            if ($blockingTask) return true;
        }
        return false;
    }
}
