<?php

namespace Concerto\PanelBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Concerto\PanelBundle\Entity\ScheduledTask;
use DateTime;

abstract class ConcertoScheduledTaskCommand extends ContainerAwareCommand {

    const FILES_BACKUP_FILENAME = "c5_files_backup.zip";
    const DB_BACKUP_FILENAME = "c5_db_backup.sql";

    protected function configure() {
        $this->addOption("task", null, InputOption::VALUE_OPTIONAL, "Task id", null);
    }

    protected function check(&$error) {
        return $this->getContainer()->get("concerto_panel.Administration_service")->isUpdatePossible($error);
    }

    protected function getTaskResultFile(ScheduledTask $task) {
        return realpath($this->getContainer()->getParameter("administration")["internal"]["backup_directory"]) . DIRECTORY_SEPARATOR . "concerto_task_" . $task->getId() . ".result";
    }

    protected function getTaskOutputFile(ScheduledTask $task) {
        return realpath($this->getContainer()->getParameter("administration")["internal"]["backup_directory"]) . DIRECTORY_SEPARATOR . "concerto_task_" . $task->getId() . ".output";
    }

    protected function getConcertoPath() {
        return realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "..");
    }

    abstract protected function getCommand(ScheduledTask $task);

    abstract public function getTaskDescription(ScheduledTask $task);

    public function getTaskInfo(ScheduledTask $task) {
        $service = $this->getContainer()->get("concerto_panel.Administration_service");
        $info = array( 
            "task_output_path" => $this->getTaskOutputFile($task),
            "task_result_path" => $this->getTaskResultFile($task)
        );
        return $info;
    }
    
    abstract public function getTaskType();

    protected function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln("checking...");
        if (!$this->check($error)) {
            $output->writeln($error);
            return 1;
        }
        $output->writeln("checks passed");

        $task_id = $input->getOption("task");

        $em = $this->getContainer()->get("doctrine")->getManager();
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

            $proc = new Process($this->getCommand($task));
            $return_code = $proc->run();
            if ($return_code !== 0) {
                $output->writeln("failed to start task #" . $task->getId() . "!");
                return $return_code;
            }
            $output->writeln("task #" . $task->getId() . " started");
        } else {
            //SCHEDULE TASK

            $task = new ScheduledTask();
            $task->setType($this->getTaskType());
            $task->setDescription($this->getTaskDescription($task));
            $tasksRepo->save($task);
            $task->setInfo(json_encode($this->getTaskInfo($task)));
            $tasksRepo->save($task);

            $output->writeln("task #" . $task->getId() . " scheduled");
        }
    }

}
