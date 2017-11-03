<?php

namespace Concerto\PanelBundle\Command;

use Concerto\PanelBundle\Command\ConcertoScheduledTaskCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DateTime;
use Concerto\PanelBundle\Entity\ScheduledTask;
use Symfony\Component\Process\Process;

class ConcertoRestoreCommand extends ConcertoScheduledTaskCommand {

    protected function configure() {
        $this->setName("concerto:restore")->setDescription("Restore backed up Concerto Platform.");

        parent::configure();
    }

    protected function check(&$error, &$code, InputInterface $input) {
        $check = parent::check($error, $code, $input);
        if (!$check)
            return false;

        $service = $this->getContainer()->get("concerto_panel.Administration_service");

        //platform backup exists
        $backup_platform_path = $service->getBackupPlatformPath();
        if (!$backup_platform_path || !file_exists($backup_platform_path)) {
            $error = "files backup not found!";
            $code = 1;
            return false;
        }
        //databse backup exists
        $backup_database_path = $service->getBackupDatabasePath();
        if (!$backup_database_path || !file_exists($backup_database_path)) {
            $error = "database backup not found!";
            $code = 1;
            return false;
        }
        return true;
    }

    protected function getCommand(ScheduledTask $task, InputInterface $input) {
        $concerto_path = $this->getConcertoPath();
        $service = $this->getContainer()->get("concerto_panel.Administration_service");
        $pb_path = $service->getBackupPlatformPath();
        $db_path = $service->getBackupDatabasePath();
        $task_output_file = $this->getTaskOutputFile($task);
        $task_result_file = $this->getTaskResultFile($task);

        $doctrine = $this->getContainer()->get('doctrine');
        $upgrade_connection = $this->getContainer()->getParameter("administration")["internal"]["upgrade_connection"];
        $connection = $doctrine->getConnection($upgrade_connection);
        $user = $connection->getUsername();
        $pass = $connection->getPassword();
        $name = $connection->getDatabase();
        $db_host = $connection->getHost();
        $db_port = $connection->getPort();

        $web_user = $this->getContainer()->getParameter("administration")["internal"]["web_user"];
        $web_group = $this->getContainer()->getParameter("administration")["internal"]["web_group"];
        $php_exec = $this->getContainer()->getParameter("test_runner_settings")["php_exec"];
        $console_path = realpath($concerto_path . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "console");
        $web_path = realpath($concerto_path . DIRECTORY_SEPARATOR . "web");
        $concerto_rlib_path = realpath($concerto_path . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR . "Concerto" . DIRECTORY_SEPARATOR . "TestBundle" . DIRECTORY_SEPARATOR . "Resources" . DIRECTORY_SEPARATOR . "R" . DIRECTORY_SEPARATOR . "concerto5");
        $r_lib_path = $this->getContainer()->getParameter("administration")["internal"]["r_lib_path"];
        $r_exec_path = $this->getContainer()->getParameter("administration")["internal"]["r_exec_path"];

        $cmd = "nohup sh -c \"sleep 3 ";
        $cmd .= "&& rm -rf $concerto_path ";
        $cmd .= "&& unzip -q $pb_path -d / ";
        $cmd .= "&& $php_exec $console_path assets:install --symlink $web_path";
        $cmd .= "&& $r_exec_path CMD INSTALL $concerto_rlib_path " . ($r_lib_path ? "-l $r_lib_path " : "");
        $cmd .= "&& chown -R $web_user:$web_group $concerto_path ";
        $cmd .= "&& mysql -h$db_host -P$db_port -u$user -p$pass $name < $db_path ";
        $cmd .= "&& echo 0 > $task_result_file || echo 1 > $task_result_file \" > $task_output_file 2>&1 & echo $! ";
        return $cmd;
    }

    public function getTaskDescription(ScheduledTask $task) {
        $service = $this->getContainer()->get("concerto_panel.Administration_service");
        $desc = $this->getContainer()->get('templating')->render("ConcertoPanelBundle:Administration:task_restore.html.twig", array(
            "backup_platform_version" => $service->getBackupPlatformVersion(),
            "backup_content_version" => $service->getBackupContentVersion()
        ));
        return $desc;
    }

    public function getTaskType() {
        return ScheduledTask::TYPE_RESTORE_BACKUP;
    }

}
