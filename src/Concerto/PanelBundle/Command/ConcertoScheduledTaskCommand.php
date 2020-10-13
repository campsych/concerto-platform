<?php

namespace Concerto\PanelBundle\Command;

use Concerto\PanelBundle\Repository\ScheduledTaskRepository;
use Concerto\PanelBundle\Service\AdministrationService;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Concerto\PanelBundle\Entity\ScheduledTask;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Input\ArrayInput;

abstract class ConcertoScheduledTaskCommand extends Command
{
    protected $administrationService;
    protected $administration;
    protected $doctrine;
    protected $scheduledTaskRepository;

    public function __construct(AdministrationService $administrationService, $administration, ManagerRegistry $doctrine, ScheduledTaskRepository $scheduledTaskRepository)
    {
        $this->administrationService = $administrationService;
        $this->administration = $administration;
        $this->doctrine = $doctrine;
        $this->scheduledTaskRepository = $scheduledTaskRepository;

        parent::__construct();
    }

    protected function configure()
    {
        $this->addOption("task", null, InputOption::VALUE_OPTIONAL, "Task id", null);
        $this->addOption("cancel-pending-on-fail", null, InputOption::VALUE_NONE, "Cancels all other pending tasks when this task fails", null);
        $this->addOption("instant-run", null, InputOption::VALUE_NONE, "Instant run without schedule", null);
        $this->addOption("content-block", null, InputOption::VALUE_REQUIRED, "Blocks panel when pending or ongoing", 0);
    }

    protected function check(&$error, &$code, InputInterface $input)
    {
        return true;
    }

    abstract protected function executeTask(ScheduledTask $task, OutputInterface $output);

    abstract public function getTaskDescription(ScheduledTask $task);

    public function getTaskInfo(InputInterface $input)
    {
        return [
            "cancel_pending_on_fail" => $input->getOption("cancel-pending-on-fail"),
            "content_block" => $input->getOption("content-block")
        ];
    }

    abstract public function getTaskType();

    protected function onBeforeTaskCreate(InputInterface $input, OutputInterface $output)
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("checking...");
        if (!$this->check($error, $code, $input)) {
            $output->writeln($error);
            return $code;
        }
        $output->writeln("checks passed");

        $task_id = $input->getOption("task");
        $instantRun = $input->getOption("instant-run");

        $task = null;

        $execute = $task_id || $instantRun;
        if (!$task_id) {
            //SCHEDULE TASK

            $this->onBeforeTaskCreate($input, $output);
            $info = $this->getTaskInfo($input);
            $contentBlock = !$instantRun && $input->getOption("content-block");

            $task = new ScheduledTask();
            $task->setType($this->getTaskType());
            $task->setInfo(json_encode($info));
            $task->setDescription($this->getTaskDescription($task));
            $this->scheduledTaskRepository->save($task);

            if ($contentBlock) $this->administrationService->setContentBlock(true);

            $output->writeln("task #" . $task->getId() . " scheduled");
            $task_id = $task->getId();
        }
        if ($execute) {
            //EXECUTE TASK

            /** @var ScheduledTask $task */
            $task = $this->scheduledTaskRepository->find($task_id);
            if (!$task) {
                $output->writeln("invalid task id!");
                $task->setStatus(ScheduledTask::STATUS_FAILED);
                $this->scheduledTaskRepository->save($task);
                $this->onTaskFinished($task, $output);
                return 1;
            }

            $task->setStatus(ScheduledTask::STATUS_ONGOING);
            $this->scheduledTaskRepository->save($task);

            $return_code = $this->executeTask($task, $output);
            if ($return_code !== 0) {
                $output->writeln("task #" . $task->getId() . " failed");
                $task->setStatus(ScheduledTask::STATUS_FAILED);
                $this->scheduledTaskRepository->save($task);
                $this->onTaskFinished($task, $output);
                return $return_code;
            }

            $output->writeln("task #" . $task->getId() . " finished successfully");
            $task->setStatus(ScheduledTask::STATUS_COMPLETED);
            $this->scheduledTaskRepository->save($task);

            $this->onTaskFinished($task, $output);
            return 0;
        }
    }

    protected function onTaskFinished(ScheduledTask $task, OutputInterface $output)
    {
        $info = json_decode($task->getInfo(), true);
        if ($task->getStatus() == ScheduledTask::STATUS_FAILED) {
            if ($info["cancel_pending_on_fail"]) {
                $this->scheduledTaskRepository->cancelPending();
            }
        }
    }
}
