<?php

namespace Concerto\PanelBundle\Command;

use Concerto\PanelBundle\Repository\ScheduledTaskRepository;
use Concerto\PanelBundle\Service\AdministrationService;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Concerto\PanelBundle\Entity\ScheduledTask;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Templating\EngineInterface;

class ConcertoTaskContentImportCommand extends ConcertoScheduledTaskCommand
{
    private $templating;
    private $kernel;

    public function __construct(AdministrationService $administrationService, $administration, ManagerRegistry $doctrine, EngineInterface $templating, KernelInterface $kernel, ScheduledTaskRepository $scheduledTaskRepository)
    {
        $this->templating = $templating;
        $this->kernel = $kernel;

        parent::__construct($administrationService, $administration, $doctrine, $scheduledTaskRepository);
    }

    protected function configure()
    {
        parent::configure();

        $this->setName("concerto:task:content:import")->setDescription("Content import");
        $this->getDefinition()->getOption("content-block")->setDefault(1);
        $this->addArgument("input", InputArgument::OPTIONAL, "Input directory", null);
        $this->addOption("instructions", "i", InputOption::VALUE_REQUIRED, "Import instructions", null);
    }

    public function getTaskDescription(ScheduledTask $task)
    {
        return $this->templating->render("@ConcertoPanel/Administration/task_content_import.html.twig", array());
    }

    public function getTaskInfo(InputInterface $input)
    {
        return array_merge(parent::getTaskInfo($input), [
            "input" => $input->getArgument("input"),
            "instructions" => $input->getOption("instructions")
        ]);
    }

    public function getTaskType()
    {
        return ScheduledTask::TYPE_CONTENT_IMPORT;
    }

    protected function executeTask(ScheduledTask $task, OutputInterface $output)
    {
        $info = json_decode($task->getInfo(), true);
        $instructions = $info["instructions"];
        $input = $info["input"];
        if ($instructions === null) $instructions = $this->administrationService->getContentTransferOptions();

        $app = new Application($this->kernel);
        $app->setAutoExit(false);
        $command = $app->find("concerto:content:import");
        $arguments = [
            "command" => $command->getName(),
            "input" => $input,
            "--instructions" => $instructions,
            "--sc" => true
        ];
        $in = new ArrayInput($arguments);
        $out = new BufferedOutput();
        $returnCode = $app->run($in, $out);
        $output->writeln($out->fetch());
        return $returnCode;
    }
}
