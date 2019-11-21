<?php

namespace Concerto\PanelBundle\Command;

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

    public function __construct(AdministrationService $administrationService, $administration, ManagerRegistry $doctrine, EngineInterface $templating, GitService $gitService)
    {
        $this->templating = $templating;
        $this->gitService = $gitService;

        parent::__construct($administrationService, $administration, $doctrine);
    }

    protected function configure()
    {
        $this->setName("concerto:task:git:pull")->setDescription("Git pull");

        $this->addArgument("username", InputArgument::OPTIONAL, "Commit username", "admin");
        $this->addArgument("email", InputArgument::OPTIONAL, "Commit email", "admin@mydomain.com");
        $this->addOption("instructions", "i", InputOption::VALUE_REQUIRED, "Import instructions", null);

        parent::configure();
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

    public function getTaskInfo(ScheduledTask $task, InputInterface $input)
    {
        $info = array_merge(parent::getTaskInfo($task, $input), array(
            "username" => $input->getArgument("username"),
            "email" => $input->getArgument("email"),
            "instructions" => $input->getOption("instructions")
        ));
        return $info;
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

        $result = $this->gitService->pull($username, $email, $instructions, $pullOutput);
        $output->writeln($pullOutput);

        $this->gitService->setGitRepoOwner();
        return $result ? 0 : 1;
    }
}
