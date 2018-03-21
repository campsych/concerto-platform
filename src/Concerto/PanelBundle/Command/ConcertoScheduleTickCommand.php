<?php

namespace Concerto\PanelBundle\Command;

use Concerto\PanelBundle\Service\AdministrationService;
use Concerto\PanelBundle\Service\MaintenanceService;
use Concerto\TestBundle\Service\TestSessionCountService;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Concerto\PanelBundle\Entity\ScheduledTask;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Concerto\PanelBundle\Entity\Message;
use DateTime;
use Symfony\Component\Templating\EngineInterface;

class ConcertoScheduleTickCommand extends Command
{
    private $administrationService;
    private $templating;
    private $doctrine;

    public function __construct(ManagerRegistry $doctrine, AdministrationService $administrationService, EngineInterface $templating)
    {
        $this->administrationService = $administrationService;
        $this->templating = $templating;
        $this->doctrine = $doctrine;

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

    private function updateOngoingTask(ScheduledTask $task, OutputInterface $output)
    {
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

        $em = $this->doctrine->getManager();
        $tasksRepo = $em->getRepository("ConcertoPanelBundle:ScheduledTask");
        $tasksRepo->save($task);

        $this->onTaskFinished($task, $output);

        return true;
    }

    private function executeTask(ScheduledTask $task, OutputInterface $output)
    {
        switch ($task->getType()) {
            case ScheduledTask::TYPE_BACKUP:
                return $this->executeBackupTask($task, $output);
            case ScheduledTask::TYPE_RESTORE_BACKUP:
                return $this->executeRestoreTask($task, $output);
            case ScheduledTask::TYPE_CONTENT_UPGRADE:
                return $this->executeContentUpgradeTask($task, $output);
            case ScheduledTask::TYPE_PLATFORM_UPGRADE:
                return $this->executePlatformUpgradeTask($task, $output);
            case ScheduledTask::TYPE_R_PACKAGE_INSTALL:
                return $this->executePackageInstallTask($task, $output);
        }
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
            if ($info["restore_backup_on_fail"]) {
                $app = $this->getApplication()->find("concerto:restore");
                $in = new ArrayInput(array(
                    "command" => "concerto:content:restore"
                ));
                $out = new BufferedOutput();
                $return_code = $app->run($in, $out);
                $response = $out->fetch();

                $output->writeln($response);
            }
        }

        switch ($task->getType()) {
            case ScheduledTask::TYPE_BACKUP:
                return $this->onBackupTaskFinished($task, $output);
            case ScheduledTask::TYPE_CONTENT_UPGRADE:
                return $this->onContentUpgradeTaskFinished($task, $output);
            case ScheduledTask::TYPE_PLATFORM_UPGRADE:
                return $this->onPlatformUpgradeTaskFinished($task, $output);
        }
    }

    private function executePackageInstallTask(ScheduledTask $task, OutputInterface $output)
    {
        $app = $this->getApplication()->find("concerto:package:install");
        $input = new ArrayInput(array(
            "command" => "concerto:package:install",
            "--task" => $task->getId()
        ));
        $bo = new BufferedOutput();
        $return_code = $app->run($input, $bo);
        $response = $bo->fetch();

        $output->writeln($response);
        $em = $this->doctrine->getManager();
        $tasksRepo = $em->getRepository("ConcertoPanelBundle:ScheduledTask");
        $task->appendOutput($response);
        $tasksRepo->save($task);

        return $return_code;
    }

    private function executeBackupTask(ScheduledTask $task, OutputInterface $output)
    {
        $app = $this->getApplication()->find("concerto:backup");
        $input = new ArrayInput(array(
            "command" => "concerto:backup",
            "--task" => $task->getId()
        ));
        $bo = new BufferedOutput();
        $return_code = $app->run($input, $bo);
        $response = $bo->fetch();

        $output->writeln($response);
        $em = $this->doctrine->getManager();
        $tasksRepo = $em->getRepository("ConcertoPanelBundle:ScheduledTask");
        $task->appendOutput($response);
        $tasksRepo->save($task);

        return $return_code;
    }

    private function executeRestoreTask(ScheduledTask $task, OutputInterface $output)
    {
        $app = $this->getApplication()->find("concerto:restore");
        $input = new ArrayInput(array(
            "command" => "concerto:restore",
            "--task" => $task->getId()
        ));
        $bo = new BufferedOutput();
        $return_code = $app->run($input, $bo);
        $response = $bo->fetch();

        $output->writeln($response);
        $em = $this->doctrine->getManager();
        $tasksRepo = $em->getRepository("ConcertoPanelBundle:ScheduledTask");
        $task->appendOutput($response);
        $tasksRepo->save($task);

        return $return_code;
    }

    private function executeContentUpgradeTask(ScheduledTask $task, OutputInterface $output)
    {
        $app = $this->getApplication()->find("concerto:content:upgrade");
        $input = new ArrayInput(array(
            "command" => "concerto:content:upgrade",
            "--task" => $task->getId()
        ));
        $bo = new BufferedOutput();
        $return_code = $app->run($input, $bo);
        $response = $bo->fetch();

        $output->writeln($response);
        $em = $this->doctrine->getManager();
        $tasksRepo = $em->getRepository("ConcertoPanelBundle:ScheduledTask");
        $task->appendOutput($response);
        $tasksRepo->save($task);

        return $return_code;
    }

    private function executePlatformUpgradeTask(ScheduledTask $task, OutputInterface $output)
    {
        $app = $this->getApplication()->find("concerto:upgrade");
        $input = new ArrayInput(array(
            "command" => "concerto:upgrade",
            "--task" => $task->getId()
        ));
        $bo = new BufferedOutput();
        $return_code = $app->run($input, $bo);
        $response = $bo->fetch();

        $output->writeln($response);
        $em = $this->doctrine->getManager();
        $tasksRepo = $em->getRepository("ConcertoPanelBundle:ScheduledTask");
        $task->appendOutput($response);
        $tasksRepo->save($task);

        return $return_code;
    }

    private function onBackupTaskFinished(ScheduledTask $task, OutputInterface $output)
    {
        if ($task->getStatus() != ScheduledTask::STATUS_COMPLETED)
            return;

        $info = json_decode($task->getInfo(), true);

        $this->administrationService->setBackupPlatformVersion($info["backup_platform_version"]);
        $this->administrationService->setBackupPlatformPath($info["backup_platform_path"]);
        $this->administrationService->setBackupDatabasePath($info["backup_database_path"]);
        $content_version = $info["backup_content_version"];
        if ($content_version === null)
            $content_version = "";
        $this->administrationService->setBackupContentVersion($content_version);
        $dt = new DateTime();
        $dt->setTimestamp($info["backup_time"]);
        $this->administrationService->setBackupTime($dt);
    }

    private function onContentUpgradeTaskFinished(ScheduledTask $task, OutputInterface $output)
    {
        if ($task->getStatus() != ScheduledTask::STATUS_COMPLETED)
            return;

        $info = json_decode($task->getInfo(), true);

        $this->administrationService->setInstalledContentVersion($info["version"]);

        $msg = new Message();
        $msg->setCagegory(Message::CATEGORY_CHANGELOG);
        $msg->setSubject("Content upgraded to v" . $info["version"]);
        $content = $this->templating->render("ConcertoPanelBundle:Administration:msg_content_upgrade.html.twig", array(
            "version" => $info["version"],
            "changelog" => json_decode($info["changelog"], true)
        ));
        $msg->setMessage($content);
        $em = $this->doctrine->getManager();
        $msgRepo = $em->getRepository("ConcertoPanelBundle:Message");
        $msgRepo->save($msg);
    }

    private function onPlatformUpgradeTaskFinished(ScheduledTask $task, OutputInterface $output)
    {
        if ($task->getStatus() != ScheduledTask::STATUS_COMPLETED)
            return;

        $info = json_decode($task->getInfo(), true);

        $msg = new Message();
        $msg->setCagegory(Message::CATEGORY_CHANGELOG);
        $msg->setSubject("Platform upgraded to v" . $info["version"]);
        $content = $this->templating->render("ConcertoPanelBundle:Administration:msg_platform_upgrade.html.twig", array(
            "version" => $info["version"],
            "changelog" => json_decode($info["changelog"], true)
        ));
        $msg->setMessage($content);
        $em = $this->doctrine->getManager();
        $msgRepo = $em->getRepository("ConcertoPanelBundle:Message");
        $msgRepo->save($msg);
    }

}
