<?php

namespace Concerto\TestBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Concerto\TestBundle\Entity\TestSessionCount;
use Concerto\TestBundle\Service\TestSessionCountService;

class LogSessionCountCommand extends Command {

    private $sessionCountService;

    public function __construct(TestSessionCountService $sessionCountService) {
        parent::__construct();

        $this->sessionCountService = $sessionCountService;
    }

    protected function configure() {
        $this->setName("concerto:sessions:log")->setDescription("Log session count.");
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->sessionCountService->updateCountRecord();
    }

}
