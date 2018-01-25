<?php

namespace Concerto\PanelBundle\Command;

use Concerto\PanelBundle\Service\AdministrationService;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Console\Input\InputInterface;
use Concerto\PanelBundle\Entity\ScheduledTask;
use Symfony\Component\Templating\EngineInterface;

class ConcertoBackupCommand extends ConcertoScheduledTaskCommand
{

    const FILES_BACKUP_FILENAME = "c5_files_backup.zip";
    const DB_BACKUP_FILENAME = "c5_db_backup.sql";

    private $templating;
    private $version;

    public function __construct(AdministrationService $administrationService, $administration, ManagerRegistry $doctrine, EngineInterface $templating, $version)
    {
        $this->templating = $templating;
        $this->version = $version;

        parent::__construct($administrationService, $administration, $doctrine);
    }

    protected function configure()
    {
        $this->setName("concerto:backup")->setDescription("Backs up Concerto Platform.");

        parent::configure();
    }

    private function getFileBackupPath()
    {
        return realpath($this->administration["internal"]["backup_directory"]) . DIRECTORY_SEPARATOR . self::FILES_BACKUP_FILENAME;
    }

    private function getDatabaseBackupPath()
    {
        return realpath($this->administration["internal"]["backup_directory"]) . DIRECTORY_SEPARATOR . self::DB_BACKUP_FILENAME;
    }

    protected function getCommand(ScheduledTask $task, InputInterface $input)
    {
        $concerto_path = $this->getConcertoPath();
        $files_backup_path = $this->getFileBackupPath();
        $db_backup_path = $this->getDatabaseBackupPath();
        $upgrade_connection = $this->administration["internal"]["upgrade_connection"];
        $connection = $this->doctrine->getConnection($upgrade_connection);
        $db_user = $connection->getUsername();
        $db_pass = $connection->getPassword();
        $db_name = $connection->getDatabase();
        $db_host = $connection->getHost();
        $db_port = $connection->getPort();
        if (!$db_port) $db_port = "3306";
        $task_result_file = $this->getTaskResultFile($task);
        $task_output_file = $this->getTaskOutputFile($task);

        $cmd = "nohup sh -c \"sleep 3 ";
        $cmd .= "&& zip -FSrq $files_backup_path $concerto_path ";
        $cmd .= "&& mysqldump -h$db_host -P$db_port -u$db_user -p$db_pass $db_name --ignore-table=$db_name.ScheduledTask > $db_backup_path ";
        $cmd .= "&& echo 0 > $task_result_file || echo 1 > $task_result_file \" > $task_output_file 2>&1 & echo $! ";
        return $cmd;
    }

    public function getTaskDescription(ScheduledTask $task)
    {
        $desc = $this->templating->render("ConcertoPanelBundle:Administration:task_backup.html.twig", array(
            "current_platform_version" => $this->version,
            "current_content_version" => $this->administrationService->getInstalledContentVersion()
        ));
        return $desc;
    }

    public function getTaskInfo(ScheduledTask $task, InputInterface $input)
    {
        $info = array_merge(parent::getTaskInfo($task, $input), array(
            "backup_platform_version" => $this->version,
            "backup_platform_path" => $this->getFileBackupPath(),
            "backup_database_path" => $this->getDatabaseBackupPath(),
            "backup_content_version" => $this->administrationService->getInstalledContentVersion(),
            "backup_time" => time()
        ));
        return $info;
    }

    public function getTaskType()
    {
        return ScheduledTask::TYPE_BACKUP;
    }

}
