<?php

namespace Concerto\PanelBundle\Command;

use Concerto\PanelBundle\Service\AdministrationService;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Concerto\PanelBundle\Entity\ScheduledTask;
use Symfony\Component\Templating\EngineInterface;

class ConcertoContentUpgradeCommand extends ConcertoScheduledTaskCommand
{
    private $templating;
    private $testRunnerSettings;

    public function __construct(AdministrationService $administrationService, $administration, ManagerRegistry $doctrine, EngineInterface $templating, $testRunnerSettings)
    {
        $this->templating = $templating;
        $this->testRunnerSettings = $testRunnerSettings;

        parent::__construct($administrationService, $administration, $doctrine);
    }

    protected function configure()
    {
        $this->setName("concerto:content:upgrade")->setDescription("Upgrades content to the latest local version.");

        $this->addOption("init-only", null, InputOption::VALUE_NONE, "Perform import only when there is no starter content yet in the system", null);

        parent::configure();
    }

    protected function check(&$error, &$code, InputInterface $input)
    {
        if (!parent::check($error, $code, $input)) return false;

        if ($input->getOption("init-only")) {
            $classes = array(
                "DataTable",
                "Test",
                "TestWizard",
                "ViewTemplate"
            );
            $em = $this->doctrine->getManager();
            foreach ($classes as $class_name) {
                $repo = $em->getRepository("ConcertoPanelBundle:" . $class_name);
                if($repo->findOneBy(array())) {
                    $error = "init-only and content already installed";
                    $code = 0;
                    return false;
                }
            }
        }
        return true;
    }

    protected function getCommand(ScheduledTask $task, InputInterface $input)
    {
        $concerto_path = $this->getConcertoPath();
        $php_exec = $this->testRunnerSettings["php_exec"];
        $console_path = realpath($concerto_path . DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "console");
        $task_result_file = $this->getTaskResultFile($task);
        $task_output_file = $this->getTaskOutputFile($task);

        $cmd = "nohup sh -c \"sleep 3 ";
        $cmd .= "&& $php_exec $console_path concerto:content:import --convert ";
        $cmd .= "&& echo 0 > $task_result_file || echo 1 > $task_result_file \" > $task_output_file 2>&1 & echo $! ";
        return $cmd;
    }

    public function getTaskDescription(ScheduledTask $task)
    {
        $desc = $this->templating->render("ConcertoPanelBundle:Administration:task_content_upgrade.html.twig", array(
            "current_version" => $this->administrationService->getInstalledContentVersion(),
            "new_version" => $this->administrationService->getAvailableContentVersion()
        ));
        return $desc;
    }

    public function getTaskInfo(ScheduledTask $task, InputInterface $input)
    {
        $info = array_merge(parent::getTaskInfo($task, $input), array(
            "changelog" => json_encode($this->administrationService->getIncrementalContentChangelog()),
            "version" => $this->administrationService->getAvailableContentVersion()
        ));
        return $info;
    }

    public function getTaskType()
    {
        return ScheduledTask::TYPE_CONTENT_UPGRADE;
    }

}
