<?php

namespace Concerto\PanelBundle\Command;

use Concerto\PanelBundle\Repository\ScheduledTaskRepository;
use Concerto\PanelBundle\Service\AdministrationService;
use Concerto\PanelBundle\Service\GitService;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Concerto\PanelBundle\Entity\ScheduledTask;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Templating\EngineInterface;

class ConcertoTaskGitPullCommand extends ConcertoScheduledTaskCommand
{
    private $templating;
    private $gitService;

    public function __construct(AdministrationService $administrationService, $administration, ManagerRegistry $doctrine, EngineInterface $templating, GitService $gitService, ScheduledTaskRepository $scheduledTaskRepository)
    {
        $this->templating = $templating;
        $this->gitService = $gitService;

        parent::__construct($administrationService, $administration, $doctrine, $scheduledTaskRepository);
    }

    protected function configure()
    {
        parent::configure();
        $this->setName("concerto:task:git:pull")->setDescription("Git pull");
        $this->getDefinition()->getOption("content-block")->setDefault(1);
        $this->addArgument("username", InputArgument::OPTIONAL, "Commit username", "admin");
        $this->addArgument("email", InputArgument::OPTIONAL, "Commit email", "admin@mydomain.com");
        $this->addOption("instructions", "i", InputOption::VALUE_REQUIRED, "Import instructions", null);
    }

    public function getTaskDescription(ScheduledTask $task)
    {
        $info = json_decode($task->getInfo(), true);
        $username = $info["username"];
        $email = $info["email"];

        $desc = $this->templating->render("@ConcertoPanel/Administration/task_git_pull.html.twig", array(
            "username" => $username,
            "email" => $email
        ));
        return $desc;
    }

    public function getTaskInfo(InputInterface $input)
    {
        return array_merge(parent::getTaskInfo($input), [
            "username" => $input->getArgument("username"),
            "email" => $input->getArgument("email"),
            "instructions" => $input->getOption("instructions")
        ]);
    }

    public function getTaskType()
    {
        return ScheduledTask::TYPE_GIT_PULL;
    }

    protected function executeTask(ScheduledTask $task, OutputInterface $output)
    {
        $info = json_decode($task->getInfo(), true);
        $username = $info["username"];
        $email = $info["email"];
        $instructions = $info["instructions"];

        $pullSuccesful = $this->gitService->pull($username, $email, $instructions, $pullOutput);
        $output->writeln($pullOutput);
        if (!$pullSuccesful) {
            return 1;
        }

        $updateSuccessful = $this->gitService->update($instructions, $updateOutput);
        $output->writeln($updateOutput);
        if (!$updateSuccessful) {
            return 1;
        }

        $this->gitService->setGitRepoOwner();
        return 0;
    }
}
