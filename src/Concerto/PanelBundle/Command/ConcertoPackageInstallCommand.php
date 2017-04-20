<?php

namespace Concerto\PanelBundle\Command;

use Concerto\PanelBundle\Command\ConcertoScheduledTaskCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Concerto\PanelBundle\Entity\ScheduledTask;
use DateTime;

class ConcertoPackageInstallCommand extends ConcertoScheduledTaskCommand {

    protected function configure() {
        $this->setName("concerto:package:install")->setDescription("Installs R package");

        $this->addOption("method", null, InputOption::VALUE_OPTIONAL, "R package install method", 0);
        $this->addOption("name", null, InputOption::VALUE_OPTIONAL, "R package name");
        $this->addOption("mirror", null, InputOption::VALUE_OPTIONAL, "R package mirror URL", "https://www.stats.bris.ac.uk/R/");
        $this->addOption("url", null, InputOption::VALUE_OPTIONAL, "R package source URL");

        parent::configure();
    }

    protected function getCommand(ScheduledTask $task, InputInterface $input) {
        $r_lib_path = $this->getContainer()->getParameter("administration")["internal"]["r_lib_path"];
        $r_exec_path = $this->getContainer()->getParameter("administration")["internal"]["r_exec_path"];
        $rscript_exec_path = $this->getContainer()->getParameter("test_runner_settings")["rscript_exec"];
        $task_output_file = $this->getTaskOutputFile($task);
        $task_result_file = $this->getTaskResultFile($task);

        $info = json_decode($task->getInfo(), true);
        $method = $info["method"];
        $name = $info["name"];
        $mirror = $info["mirror"];
        $url = $info["url"];

        if ($method == 0) {
            $lib = $r_lib_path ? ", lib='$r_lib_path'" : "";

            $cmd = "nohup sh -c \"sleep 3 ";
            $cmd .= "&& $rscript_exec_path -e \\\"install.packages(" . escapeshellarg($name) . ", repos=" . escapeshellarg($mirror) . $lib . ")\\\" ";
            $cmd .= "&& echo 0 > $task_result_file || echo 1 > $task_result_file \" > $task_output_file 2>&1 & echo $! ";
        } else {
            $lib = $r_lib_path ? "-l $r_lib_path " : "";

            $cmd = "nohup sh -c \"sleep 3 ";
            $cmd .= "&& wget -O /tmp/concerto_r_package " . escapeshellarg($url) . " ";
            $cmd .= "&& $r_exec_path CMD INSTALL /tmp/concerto_r_package " . $lib;
            $cmd .= "&& echo 0 > $task_result_file || echo 1 > $task_result_file \" > $task_output_file 2>&1 & echo $! ";
        }
        return $cmd;
    }

    public function getTaskDescription(ScheduledTask $task) {
        $info = json_decode($task->getInfo(), true);
        $method = $info["method"];
        $name = $info["name"];
        $url = $info["url"];

        $desc = $this->getContainer()->get('templating')->render("ConcertoPanelBundle:Administration:task_package_install.html.twig", array(
            "name" => $method == 0 ? $name : $url
        ));
        return $desc;
    }

    public function getTaskInfo(ScheduledTask $task, InputInterface $input) {
        $info = array_merge(parent::getTaskInfo($task, $input), array(
            "method" => $input->getOption("method"),
            "name" => $input->getOption("name"),
            "mirror" => $input->getOption("mirror"),
            "url" => $input->getOption("url")
        ));
        return $info;
    }

    public function getTaskType() {
        return ScheduledTask::TYPE_R_PACKAGE_INSTALL;
    }

}
