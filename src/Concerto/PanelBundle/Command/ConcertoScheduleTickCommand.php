<?php

namespace Concerto\PanelBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Concerto\PanelBundle\Entity\ScheduledTask;
use Concerto\PanelBundle\Command\ConcertoBackupCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Application;
use DateTime;

class ConcertoScheduleTickCommand extends ContainerAwareCommand {

    protected function configure() {
        $this->setName("concerto:schedule:tick")->setDescription("Administrative tasks tick.");
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $em = $this->getContainer()->get("doctrine")->getManager();
        $tasksRepo = $em->getRepository("ConcertoPanelBundle:ScheduledTask");

        $ongoingTasks = $tasksRepo->findAllOngoing();
        $busy = false;
        foreach ($ongoingTasks as $task) {
            $finished = $this->updateOngoingTask($task, $output);
            if (!$finished)
                $busy = true;
        }

        if ($busy)
            return 0;

        $pendingTasks = $tasksRepo->findAllPending();
        foreach ($pendingTasks as $task) {
            $return_code = $this->executeTask($task, $output);
            if ($return_code !== 0) {
                $msg = "task #" . $task->getId() . " start failed (" . $return_code . ")!";
                $output->writeln($msg);

                $task->appendOutput($msg);
                $task->setStatus(ScheduledTask::STATUS_FAILED);
                $tasksRepo->save($task);

                return $return_code;
            }
            break;
        }
        return 0;
    }

    private function updateOngoingTask(ScheduledTask $task, OutputInterface $output) {
        $info = json_decode($task->getInfo(), true);
        $output_file = $info["task_output_path"];
        $result_file = $info["task_result_path"];

        if (!file_exists($output_file) || !file_exists($result_file)) {
            return false;
        }

        $output_content = file_get_contents($output_file);
        $result_content = file_get_contents($result_file);
        unlink($output_file);
        unlink($result_file);

        $task->appendOutput($output_content);
        $task->setStatus($result_content == 0 ? ScheduledTask::STATUS_COMPLETED : ScheduledTask::STATUS_FAILED);

        $msg = "task #" . $task->getId() . " finished";
        $output->writeln($msg);
        $task->appendOutput($msg);

        $em = $this->getContainer()->get("doctrine")->getManager();
        $tasksRepo = $em->getRepository("ConcertoPanelBundle:ScheduledTask");
        $tasksRepo->save($task);

        $this->onPostTask($task, $output);

        return true;
    }

    private function executeTask(ScheduledTask $task, OutputInterface $output) {
        switch ($task->getType()) {
            case ScheduledTask::TYPE_BACKUP: return $this->executeBackupTask($task, $output);
            case ScheduledTask::TYPE_RESTORE_BACKUP: return $this->executeRestoreTask($task, $output);
        }
    }
    
    private function onPostTask(ScheduledTask $task, OutputInterface $output) {
        switch ($task->getType()) {
            case ScheduledTask::TYPE_BACKUP: return $this->onPostBackupTask($task, $output);
        }
    }

    private function executeBackupTask(ScheduledTask $task, OutputInterface $output) {
        $app = $this->getApplication()->find("concerto:backup");
        $input = new ArrayInput(array(
            "command" => "concerto:backup",
            "--task" => $task->getId()
        ));
        $bo = new BufferedOutput();
        $return_code = $app->run($input, $bo);
        $response = $bo->fetch();

        $output->writeln($response);
        $em = $this->getContainer()->get("doctrine")->getManager();
        $tasksRepo = $em->getRepository("ConcertoPanelBundle:ScheduledTask");
        $task->appendOutput($response);
        $tasksRepo->save($task);

        return $return_code;
    }

    private function executeRestoreTask(ScheduledTask $task, OutputInterface $output) {
        $app = $this->getApplication()->find("concerto:restore");
        $input = new ArrayInput(array(
            "command" => "concerto:restore",
            "--task" => $task->getId()
        ));
        $bo = new BufferedOutput();
        $return_code = $app->run($input, $bo);
        $response = $bo->fetch();

        $output->writeln($response);
        $em = $this->getContainer()->get("doctrine")->getManager();
        $tasksRepo = $em->getRepository("ConcertoPanelBundle:ScheduledTask");
        $task->appendOutput($response);
        $tasksRepo->save($task);

        return $return_code;
    }

    private function onPostBackupTask(ScheduledTask $task, OutputInterface $output) {
        $info = json_decode($task->getInfo(), true);

        $service = $this->getContainer()->get("concerto_panel.Administration_service");
        $service->setBackupPlatformVersion($info["backup_platform_version"]);
        $service->setBackupPlatformPath($info["backup_platform_path"]);
        $service->setBackupDatabasePath($info["backup_database_path"]);
        $content_version = $info["backup_content_version"];
        if ($content_version === null)
            $content_version = "";
        $service->setBackupContentVersion($content_version);
        $dt = new DateTime();
        $dt->setTimestamp($info["backup_time"]);
        $service->setBackupTime($dt);
    }
}
