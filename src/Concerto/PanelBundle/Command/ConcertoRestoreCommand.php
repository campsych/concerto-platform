<?php

namespace Concerto\PanelBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DateTime;
use Concerto\PanelBundle\Entity\ScheduledTask;
use Symfony\Component\Process\Process;

class ConcertoRestoreCommand extends ContainerAwareCommand {

    protected function configure() {
        $this->setName("concerto:restore")->setDescription("Restore backed up Concerto Platform.");
    }

    private function check(OutputInterface $output) {
        $output->writeln("checking...");

        //is update possible
        $service = $this->getContainer()->get("concerto_panel.Administration_service");
        $result = $service->isUpdatePossible($error);
        if (!$result) {
            $output->writeln($error);
            return false;
        }

        //platform backup exists
        $backup_platform_path = $service->getBackupPlatformPath();
        if (!$backup_platform_path || !file_exists($backup_platform_path)) {
            $output->writeln("files backup not found!");
            return false;
        }
        //databse backup exists
        $backup_database_path = $service->getBackupDatabasePath();
        if (!$backup_database_path || !file_exists($backup_database_path)) {
            $output->writeln("database backup not found!");
            return false;
        }

        $output->writeln("checks passed");
        return true;
    }

    private function getCommand(ScheduledTask $task) {
        $concerto_path = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "..");
        $service = $this->getContainer()->get("concerto_panel.Administration_service");
        $pb_path = $service->getBackupPlatformPath();
        $db_path = $service->getBackupDatabasePath();
        $task_output_file = realpath($this->getContainer()->getParameter("administration")["backup_directory"]) . DIRECTORY_SEPARATOR . "c5_task_" . $task->getId() . ".output";
        $task_result_file = realpath($this->getContainer()->getParameter("administration")["backup_directory"]) . DIRECTORY_SEPARATOR . "c5_task_" . $task->getId() . ".result";

        $doctrine = $this->getContainer()->get('doctrine');
        $upgrade_connection = $this->getContainer()->getParameter("administration")["upgrade_connection"];
        $connection = $doctrine->getConnection($upgrade_connection);
        $user = $connection->getUsername();
        $pass = $connection->getPassword();
        $name = $connection->getDatabase();

        $web_user = $this->getContainer()->getParameter("administration")["web_user"];
        $web_group = $this->getContainer()->getParameter("administration")["web_group"];

        //@TODO we might need to add assets install here 
        $cmd = "nohup sh -c \"sleep 3 ";
        $cmd .= "&& rm -rf $concerto_path ";
        $cmd .= "&& unzip -q $pb_path -d / ";
        $cmd .= "&& chown -R $web_user:$web_group $concerto_path ";
        $cmd .= "&& mysql -u$user -p$pass $name < $db_path ";
        $cmd .= "&& echo $? > $task_result_file \" > $task_output_file 2>&1 & echo $! "; 
        return $cmd; 
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        if (!$this->check($output))
            return 1;

        $service = $this->getContainer()->get("concerto_panel.Administration_service");
        $task = $service->createRestoreTask();
        $cmd = $this->getCommand($task);
        $process = new Process($cmd);
        $process->run();

        $output->writeln("restore initiated");
    }

}
