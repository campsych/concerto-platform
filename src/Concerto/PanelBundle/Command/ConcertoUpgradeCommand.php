<?php

namespace Concerto\PanelBundle\Command;

use Concerto\PanelBundle\Command\ConcertoScheduledTaskCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Concerto\PanelBundle\Entity\ScheduledTask;
use DateTime;

class ConcertoUpgradeCommand extends ConcertoScheduledTaskCommand {

    protected function configure() {
        $this->setName("concerto:upgrade")->setDescription("Upgrades platform to latest version.");

        parent::configure();
    }

    protected function check(&$error, &$code, InputInterface $input) {
        $check = parent::check($error, $code, $input);
        if (!$check)
            return false;

        $service = $this->getContainer()->get("concerto_panel.Administration_service");

        //check if curl exists
        $cmd = 'command -v curl >/dev/null 2>&1 || { exit 1; }';
        system($cmd, $return_var);
        if ($return_var !== 0) {
            $error = "no curl found!";
            $code = 1;
            return false;
        }

        //check if git exists
        $cmd = 'command -v git >/dev/null 2>&1 || { exit 1; }';
        system($cmd, $return_var);
        if ($return_var !== 0) {
            $error = "no git found!";
            $code = 1;
            return false;
        }

        //check if bower exists
        $cmd = 'command -v bower >/dev/null 2>&1 || { exit 1; }';
        system($cmd, $return_var);
        if ($return_var !== 0) {
            $error = "no bower found!";
            $code = 1;
            return false;
        }
        return true;
    }

    protected function getCommand(ScheduledTask $task, InputInterface $input) {
        $service = $this->getContainer()->get("concerto_panel.Administration_service");
        $concerto_path = $this->getConcertoPath();
        $php_exec = $this->getContainer()->getParameter("test_runner_settings")["php_exec"];
        $console_path = realpath($concerto_path . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "console");
        $web_path = realpath($concerto_path . DIRECTORY_SEPARATOR . "web");
        $panel_bower_path = realpath($concerto_path . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR . "Concerto" . DIRECTORY_SEPARATOR . "PanelBundle" . DIRECTORY_SEPARATOR . "Resources" . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "angularjs");
        $test_bower_path = realpath($concerto_path . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR . "Concerto" . DIRECTORY_SEPARATOR . "TestBundle" . DIRECTORY_SEPARATOR . "Resources" . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "angularjs");
        $git_branch = $service->getGitBranch();
        $concerto_rlib_path = realpath($concerto_path . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR . "Concerto" . DIRECTORY_SEPARATOR . "TestBundle" . DIRECTORY_SEPARATOR . "Resources" . DIRECTORY_SEPARATOR . "R" . DIRECTORY_SEPARATOR . "concerto5");
        $web_user = $this->getContainer()->getParameter("administration")["internal"]["web_user"];
        $web_group = $this->getContainer()->getParameter("administration")["internal"]["web_group"];
        $r_lib_path = $this->getContainer()->getParameter("administration")["internal"]["r_lib_path"];
        $r_exec_path = $this->getContainer()->getParameter("administration")["internal"]["r_exec_path"];
        $task_result_file = $this->getTaskResultFile($task);
        $task_output_file = $this->getTaskOutputFile($task);

        $cmd = "nohup sh -c \"sleep 3 ";
        $cmd .= "&& cd $concerto_path ";
        $cmd .= "&& git fetch origin ";
        $cmd .= "&& git reset --hard origin/$git_branch ";
        $cmd .= "&& curl -s http://getcomposer.org/installer | php ";
        $cmd .= "&& $php_exec -dmemory_limit=1G composer.phar install --no-interaction ";
        $cmd .= "&& cd $panel_bower_path ";
        $cmd .= "&& bower install --allow-root ";
        $cmd .= "&& cd $test_bower_path ";
        $cmd .= "&& bower install --allow-root ";
        $cmd .= "&& $r_exec_path CMD INSTALL $concerto_rlib_path " . ($r_lib_path ? "-l $r_lib_path " : "");
        $cmd .= "&& $php_exec $console_path concerto:setup ";
        $cmd .= "&& $php_exec $console_path concerto:r:cache ";
        $cmd .= "&& $php_exec $console_path cache:clear ";
        $cmd .= "&& $php_exec $console_path assets:install --symlink $web_path";
        $cmd .= "&& chown -R $web_user:$web_group $concerto_path ";
        $cmd .= "&& echo 0 > $task_result_file || echo 1 > $task_result_file \" > $task_output_file 2>&1 & echo $! ";
        return $cmd;
    }

    public function getTaskDescription(ScheduledTask $task) {
        $service = $this->getContainer()->get("concerto_panel.Administration_service");
        $desc = $this->getContainer()->get('templating')->render("ConcertoPanelBundle:Administration:task_platform_upgrade.html.twig", array(
            "current_version" => $service->getInstalledPlatformVersion(),
            "new_version" => $service->getAvailablePlatformVersion()
        ));
        return $desc;
    }

    public function getTaskInfo(ScheduledTask $task, InputInterface $input) {
        $service = $this->getContainer()->get("concerto_panel.Administration_service");
        $info = array_merge(parent::getTaskInfo($task, $input), array(
            "changelog" => json_encode($service->getIncrementalPlatformChangelog()),
            "version" => $service->getAvailablePlatformVersion()
        ));
        return $info;
    }

    public function getTaskType() {
        return ScheduledTask::TYPE_PLATFORM_UPGRADE;
    }

}
