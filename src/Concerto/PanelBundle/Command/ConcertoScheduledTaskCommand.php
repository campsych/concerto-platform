<?php

namespace Concerto\PanelBundle\Command;

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

    const FILES_BACKUP_FILENAME = "c5_files_backup.zip";
    const DB_BACKUP_FILENAME = "c5_db_backup.sql";

    protected $administrationService;
    protected $administration;
    protected $doctrine;

    public function __construct(AdministrationService $administrationService, $administration, ManagerRegistry $doctrine)
    {
        $this->administrationService = $administrationService;
        $this->administration = $administration;
        $this->doctrine = $doctrine;

        parent::__construct();
    }

    protected function configure()
    {
        $this->addOption("task", null, InputOption::VALUE_OPTIONAL, "Task id", null);
        $this->addOption("cancel-pending-on-fail", null, InputOption::VALUE_NONE, "Cancels all other pending tasks when this task fails", null);
        $this->addOption("backup", null, InputOption::VALUE_NONE, "Perform backup and use it as restore point when content upgrade task will fail.", null);
    }

    protected function check(&$error, &$code, InputInterface $input)
    {
        return $this->administrationService->isUpdatePossible($error);
    }

    protected function getTaskResultFile(ScheduledTask $task)
    {
        return realpath($this->administration["internal"]["backup_directory"]) . DIRECTORY_SEPARATOR . "concerto_task_" . $task->getId() . ".result";
    }

    protected function getTaskOutputFile(ScheduledTask $task)
    {
        return realpath($this->administration["internal"]["backup_directory"]) . DIRECTORY_SEPARATOR . "concerto_task_" . $task->getId() . ".output";
    }

    protected function getConcertoPath()
    {
        return realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "..");
    }

    abstract protected function getCommand(ScheduledTask $task, InputInterface $input);

    abstract public function getTaskDescription(ScheduledTask $task);

    public function getTaskInfo(ScheduledTask $task, InputInterface $input)
    {
        $info = array(
            "task_output_path" => $this->getTaskOutputFile($task),
            "task_result_path" => $this->getTaskResultFile($task),
            "cancel_pending_on_fail" => $input->getOption("cancel-pending-on-fail"),
            "restore_backup_on_fail" => $input->getOption("backup")
        );
        return $info;
    }

    abstract public function getTaskType();

    protected function onBeforeTaskCreate(InputInterface $input, OutputInterface $output)
    {
        $backup = $input->getOption("backup");
        if ($backup) {
            $output->writeln("restore point creation requested");

            $app = $this->getApplication()->find('concerto:backup');
            $in = new ArrayInput(array(
                'command' => 'concerto:backup',
                '--cancel-pending-on-fail' => true
            ));
            $out = new BufferedOutput();
            $return_code = $app->run($in, $out);
            $response = $out->fetch();
            $output->writeln($response);
        }
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

        $em = $this->doctrine->getManager();
        $tasksRepo = $em->getRepository("ConcertoPanelBundle:ScheduledTask");
        $task = null;
        if ($task_id) {
            //START TASK

            $task = $tasksRepo->find($task_id);
            if (!$task) {
                $output->writeln("invalid task id!");
                return 1;
            }

            $task->setStatus(ScheduledTask::STATUS_ONGOING);
            $tasksRepo->save($task);

            $cmd = $this->getCommand($task, $input);
            //$output->writeln($cmd);
            $proc = new Process($cmd);
            $return_code = $proc->run();
            if ($return_code !== 0) {
                $output->writeln("failed to start task #" . $task->getId() . "!");
                return $return_code;
            }
            $output->writeln("task #" . $task->getId() . " started");
        } else {
            //SCHEDULE TASK

            $this->onBeforeTaskCreate($input, $output);

            $task = new ScheduledTask();
            $task->setType($this->getTaskType());
            $tasksRepo->save($task);
            $task->setInfo(json_encode($this->getTaskInfo($task, $input)));
            $task->setDescription($this->getTaskDescription($task));
            $tasksRepo->save($task);

            $output->writeln("task #" . $task->getId() . " scheduled");
        }
    }

}
