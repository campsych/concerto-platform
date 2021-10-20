<?php

namespace Concerto\TestBundle\Command;

use Concerto\PanelBundle\Service\TestSessionService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunTestCommand extends Command
{
    private $sessionService;

    public function __construct(TestSessionService $sessionService)
    {
        parent::__construct();

        $this->sessionService = $sessionService;
    }

    protected function configure()
    {
        $this->setName("concerto:test:run")->setDescription("Run test");
        $this->addArgument("name", InputArgument::REQUIRED, "Test name");
        $this->addArgument("params", InputArgument::OPTIONAL, "{}");
        $this->addOption("debug", null, InputOption::VALUE_NONE, "print debug output?");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument("name");
        $params = $input->getArgument("params");
        $debug = $input->getOption("debug");

        $result = $this->sessionService->startNewSession(null, $name, $params, array(), array(), "CLI", "CLI", $debug, false, 0, false);
        if (isset($result["debug"])) {
            $output->writeln($result["debug"]);
        } else {
            $output->writeln(json_encode($result));
        }
    }

}
