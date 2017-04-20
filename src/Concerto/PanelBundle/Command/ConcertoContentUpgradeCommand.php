<?php

namespace Concerto\PanelBundle\Command;

use Concerto\PanelBundle\Command\ConcertoScheduledTaskCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Concerto\PanelBundle\Entity\ScheduledTask;
use DateTime;

class ConcertoContentUpgradeCommand extends ConcertoScheduledTaskCommand {

    protected function configure() {
        $this->setName("concerto:content:upgrade")->setDescription("Upgrades content to the latest local version.");

        parent::configure();
    }

    protected function getCommand(ScheduledTask $task, InputInterface $input) {
        $concerto_path = $this->getConcertoPath();
        $php_exec = $this->getContainer()->getParameter("test_runner_settings")["php_exec"];
        $console_path = realpath($concerto_path . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "console");
        $task_result_file = $this->getTaskResultFile($task);
        $task_output_file = $this->getTaskOutputFile($task);

        $cmd = "nohup sh -c \"sleep 3 ";
        $cmd .= "&& $php_exec $console_path concerto:content:import --convert ";
        $cmd .= "&& echo 0 > $task_result_file || echo 1 > $task_result_file \" > $task_output_file 2>&1 & echo $! ";
        return $cmd;
    }

    public function getTaskDescription(ScheduledTask $task) {
        $service = $this->getContainer()->get("concerto_panel.Administration_service");
        $desc = $this->getContainer()->get('templating')->render("ConcertoPanelBundle:Administration:task_content_upgrade.html.twig", array(
            "current_version" => $service->getInstalledContentVersion(),
            "new_version" => $service->getAvailableContentVersion()
        ));
        return $desc;
    }

    public function getTaskInfo(ScheduledTask $task, InputInterface $input) {
        $service = $this->getContainer()->get("concerto_panel.Administration_service");
        $info = array_merge(parent::getTaskInfo($task, $input), array(
            "changelog" => json_encode($service->getIncrementalContentChangelog()),
            "version" => $service->getAvailableContentVersion()
        ));
        return $info;
    }

    public function getTaskType() {
        return ScheduledTask::TYPE_CONTENT_UPGRADE;
    }

}
