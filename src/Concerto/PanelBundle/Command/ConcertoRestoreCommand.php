<?php

namespace Concerto\PanelBundle\Command;

use Concerto\PanelBundle\Service\AdministrationService;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Console\Input\InputInterface;
use Concerto\PanelBundle\Entity\ScheduledTask;
use Symfony\Component\Templating\EngineInterface;

class ConcertoRestoreCommand extends ConcertoScheduledTaskCommand
{

    private $testRunnerSettings;
    private $templating;

    public function __construct(AdministrationService $administrationService, $administration, ManagerRegistry $doctrine, $testRunnerSettings, EngineInterface $templating)
    {
        $this->testRunnerSettings = $testRunnerSettings;
        $this->templating = $templating;

        parent::__construct($administrationService, $administration, $doctrine);
    }

    protected function configure()
    {
        $this->setName("concerto:restore")->setDescription("Restore backed up Concerto Platform.");

        parent::configure();
    }

    protected function check(&$error, &$code, InputInterface $input)
    {
        $check = parent::check($error, $code, $input);
        if (!$check)
            return false;

        //platform backup exists
        $backup_platform_path = $this->administrationService->getBackupPlatformPath();
        if (!$backup_platform_path || !file_exists($backup_platform_path)) {
            $error = "files backup not found!";
            $code = 1;
            return false;
        }
        //databse backup exists
        $backup_database_path = $this->administrationService->getBackupDatabasePath();
        if (!$backup_database_path || !file_exists($backup_database_path)) {
            $error = "database backup not found!";
            $code = 1;
            return false;
        }
        return true;
    }

    protected function getCommand(ScheduledTask $task, InputInterface $input)
    {
        $concerto_path = $this->getConcertoPath();
        $pb_path = $this->administrationService->getBackupPlatformPath();
        $db_path = $this->administrationService->getBackupDatabasePath();
        $task_output_file = $this->getTaskOutputFile($task);
        $task_result_file = $this->getTaskResultFile($task);

        $upgrade_connection = $this->administration["internal"]["upgrade_connection"];
        $connection = $this->doctrine->getConnection($upgrade_connection);
        $user = $connection->getUsername();
        $pass = $connection->getPassword();
        $name = $connection->getDatabase();
        $db_host = $connection->getHost();
        $db_port = $connection->getPort();

        $web_user = $this->administration["internal"]["web_user"];
        $web_group = $this->administration["internal"]["web_group"];
        $php_exec = $this->testRunnerSettings["php_exec"];
        $console_path = realpath($concerto_path . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "console");
        $web_path = realpath($concerto_path . DIRECTORY_SEPARATOR . "web");
        $concerto_rlib_path = realpath($concerto_path . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR . "Concerto" . DIRECTORY_SEPARATOR . "TestBundle" . DIRECTORY_SEPARATOR . "Resources" . DIRECTORY_SEPARATOR . "R" . DIRECTORY_SEPARATOR . "concerto5");
        $r_lib_path = $this->administration["internal"]["r_lib_path"];
        $r_exec_path = $this->administration["internal"]["r_exec_path"];

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

    public function getTaskDescription(ScheduledTask $task)
    {
        $desc = $this->templating->render("ConcertoPanelBundle:Administration:task_restore.html.twig", array(
            "backup_platform_version" => $this->administrationService->getBackupPlatformVersion(),
            "backup_content_version" => $this->administrationService->getBackupContentVersion()
        ));
        return $desc;
    }

    public function getTaskType()
    {
        return ScheduledTask::TYPE_RESTORE_BACKUP;
    }

}
