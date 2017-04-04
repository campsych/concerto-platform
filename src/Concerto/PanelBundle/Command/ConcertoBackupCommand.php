<?php

namespace Concerto\PanelBundle\Command;

use Concerto\PanelBundle\Command\ConcertoScheduledTaskCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Concerto\PanelBundle\Entity\ScheduledTask;
use DateTime;

class ConcertoBackupCommand extends ConcertoScheduledTaskCommand {

    const FILES_BACKUP_FILENAME = "c5_files_backup.zip";
    const DB_BACKUP_FILENAME = "c5_db_backup.sql";

    protected function configure() {
        $this->setName("concerto:backup")->setDescription("Backs up Concerto Platform.");

        parent::configure();
    }

    private function getFileBackupPath() {
        return realpath($this->getContainer()->getParameter("administration")["internal"]["backup_directory"]) . DIRECTORY_SEPARATOR . self::FILES_BACKUP_FILENAME;
    }

    private function getDatabaseBackupPath() {
        return realpath($this->getContainer()->getParameter("administration")["internal"]["backup_directory"]) . DIRECTORY_SEPARATOR . self::DB_BACKUP_FILENAME;
    }

    protected function getCommand(ScheduledTask $task) {
        $concerto_path = $this->getConcertoPath();
        $files_backup_path = $this->getFileBackupPath();
        $db_backup_path = $this->getDatabaseBackupPath();
        $doctrine = $this->getContainer()->get('doctrine');
        $upgrade_connection = $this->getContainer()->getParameter("administration")["internal"]["upgrade_connection"];
        $connection = $doctrine->getConnection($upgrade_connection);
        $db_user = $connection->getUsername();
        $db_pass = $connection->getPassword();
        $db_name = $connection->getDatabase();
        $task_result_file = $this->getTaskResultFile($task);
        $task_output_file = $this->getTaskOutputFile($task);

        $cmd = "nohup sh -c \"sleep 3 ";
        $cmd .= "&& zip -FSrq $files_backup_path $concerto_path ";
        $cmd .= "&& mysqldump -u$db_user -p$db_pass $db_name --ignore-table=$db_name.ScheduledTask > $db_backup_path ";
        $cmd .= "&& echo 0 > $task_result_file || echo 1 > $task_result_file \" > $task_output_file 2>&1 & echo $! ";
        return $cmd;
    }

    public function getTaskDescription(ScheduledTask $task) {
        $service = $this->getContainer()->get("concerto_panel.Administration_service");
        $desc = $this->getContainer()->get('templating')->render("ConcertoPanelBundle:Administration:task_backup.html.twig", array(
            "current_platform_version" => $this->getContainer()->getParameter("version"),
            "current_content_version" => $service->getInstalledContentVersion()
        ));
        return $desc;
    }

    public function getTaskInfo(ScheduledTask $task) {
        $service = $this->getContainer()->get("concerto_panel.Administration_service");
        $info = array_merge(parent::getTaskInfo($task), array(
            "backup_platform_version" => $this->getContainer()->getParameter("version"),
            "backup_platform_path" => $this->getFileBackupPath(),
            "backup_database_path" => $this->getDatabaseBackupPath(),
            "backup_content_version" => $service->getInstalledContentVersion(),
            "backup_time" => time()
        ));
        return $info;
    }

    public function getTaskType() {
        return ScheduledTask::TYPE_BACKUP;
    }

}
