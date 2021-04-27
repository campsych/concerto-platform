<?php

namespace Concerto\PanelBundle\Command;

use Concerto\PanelBundle\Repository\ScheduledTaskRepository;
use Concerto\PanelBundle\Service\AdministrationService;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Concerto\PanelBundle\Entity\ScheduledTask;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Templating\EngineInterface;

class ConcertoTaskPackageInstallCommand extends ConcertoScheduledTaskCommand
{
    private $testRunnerSettings;
    private $templating;

    public function __construct(AdministrationService $administrationService, $administration, ManagerRegistry $doctrine, $testRunnerSettings, EngineInterface $templating, ScheduledTaskRepository $scheduledTaskRepository)
    {
        $this->testRunnerSettings = $testRunnerSettings;
        $this->templating = $templating;

        parent::__construct($administrationService, $administration, $doctrine, $scheduledTaskRepository);
    }

    protected function configure()
    {
        parent::configure();
        $this->setName("concerto:task:package:install")->setDescription("Installs R package");
        $this->addOption("method", null, InputOption::VALUE_OPTIONAL, "R package install method", 0);
        $this->addOption("name", null, InputOption::VALUE_OPTIONAL, "R package name");
        $this->addOption("mirror", null, InputOption::VALUE_OPTIONAL, "R package mirror URL", "https://www.stats.bris.ac.uk/R/");
        $this->addOption("url", null, InputOption::VALUE_OPTIONAL, "R package source URL");
    }

    protected function getCommand(ScheduledTask $task)
    {
        $r_lib_path = $this->administration["internal"]["r_lib_path"];
        $r_exec_path = $this->administration["internal"]["r_exec_path"];
        $rscript_exec_path = $this->testRunnerSettings["rscript_exec"];

        $info = json_decode($task->getInfo(), true);
        $method = $info["method"];
        $name = $info["name"];
        $mirror = $info["mirror"];
        $url = $info["url"];

        if ($method == 0) {
            $lib = $r_lib_path ? ", lib='$r_lib_path'" : "";

            $cmd = "$rscript_exec_path -e \"install.packages(" . escapeshellarg($name) . ", repos=" . escapeshellarg($mirror) . $lib . ")\"";
        } else {
            $lib = $r_lib_path ? "-l $r_lib_path " : "";

            $cmd = "wget -O /tmp/concerto_r_package " . escapeshellarg($url) . " ";
            $cmd .= "&& $r_exec_path CMD INSTALL /tmp/concerto_r_package " . $lib;
        }
        return $cmd;
    }

    public function getTaskDescription(ScheduledTask $task)
    {
        $info = json_decode($task->getInfo(), true);
        $method = $info["method"];
        $name = $info["name"];
        $url = $info["url"];

        return $this->templating->render("ConcertoPanelBundle:Administration:task_package_install.html.twig", array(
            "name" => $method == 0 ? $name : $url
        ));
    }

    public function getTaskInfo(InputInterface $input)
    {
        return array_merge(parent::getTaskInfo($input), [
            "method" => $input->getOption("method"),
            "name" => $input->getOption("name"),
            "mirror" => $input->getOption("mirror"),
            "url" => $input->getOption("url")
        ]);
    }

    public function getTaskType()
    {
        return ScheduledTask::TYPE_R_PACKAGE_INSTALL;
    }

    protected function executeTask(ScheduledTask $task, OutputInterface $output)
    {
        $cmd = $this->getCommand($task);
        $proc = new Process($cmd);
        $proc->setTimeout(null);
        $result = $proc->run();
        $output->writeln($proc->getOutput());
        $error = $proc->getErrorOutput();
        if ($error) $output->writeln($error);
        return $result;
    }
}
