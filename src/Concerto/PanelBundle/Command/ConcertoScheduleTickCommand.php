<?php

namespace Concerto\PanelBundle\Command;

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

    private $tasksDefinition = array(
        ScheduledTask::TYPE_R_PACKAGE_INSTALL => array(
            "name" => "concerto:task:package:install",
            "options" => []
        ),
        ScheduledTask::TYPE_GIT_PULL => array(
            "name" => "concerto:task:git:pull",
            "options" => []
        ),
        ScheduledTask::TYPE_GIT_ENABLE => array(
            "name" => "concerto:task:git:enable",
            "options" => []
        ),
        ScheduledTask::TYPE_GIT_UPDATE => array(
            "name" => "concerto:task:git:update",
            "options" => []
        )
    );

    public function __construct(ManagerRegistry $doctrine, AdministrationService $administrationService, EngineInterface $templating, GitService $gitService)
    {
        $this->administrationService = $administrationService;
        $this->templating = $templating;
        $this->doctrine = $doctrine;
        $this->gitService = $gitService;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("concerto:schedule:tick")->setDescription("Administrative tasks tick.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->doctrine->getManager();
        $tasksRepo = $em->getRepository("ConcertoPanelBundle:ScheduledTask");
        foreach ($tasksRepo->findAllPending() as $task) {
            $this->executeTask($task, $output);
        }
        return 0;
    }

    private function executeTask(ScheduledTask $task, OutputInterface $output)
    {
        $def = $this->tasksDefinition[$task->getType()];

        $app = $this->getApplication()->find($def["name"]);
        $input = new ArrayInput(array_merge(array(
            "command" => $def["name"],
            "--task" => $task->getId()
        ), $def["options"]));
        $bo = new BufferedOutput();
        $return_code = $app->run($input, $bo);
        $response = $bo->fetch();

        $em = $this->doctrine->getManager();
        $tasksRepo = $em->getRepository("ConcertoPanelBundle:ScheduledTask");
        $task->appendOutput($response);
        $output->writeln($response);
        $task->setStatus($return_code == 0 ? ScheduledTask::STATUS_COMPLETED : ScheduledTask::STATUS_FAILED);
        $tasksRepo->save($task);

        $this->onTaskFinished($task, $output);

        return $return_code;
    }

    private function onTaskFinished(ScheduledTask $task, OutputInterface $output)
    {
        $info = json_decode($task->getInfo(), true);
        if ($task->getStatus() == ScheduledTask::STATUS_FAILED) {
            if ($info["cancel_pending_on_fail"]) {
                $em = $this->doctrine->getManager();
                $tasksRepo = $em->getRepository("ConcertoPanelBundle:ScheduledTask");
                $tasksRepo->cancelPending();
            }
        }
    }
}
