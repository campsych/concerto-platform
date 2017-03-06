<?php

namespace Concerto\TestBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Concerto\TestBundle\Service\RRunnerService;
use Concerto\TestBundle\Entity\SessionCount;
use Concerto\TestBundle\Service\SessionCountService;

class LogSessionCountCommand extends Command {

    private $rRunnerService;
    private $sessionCountService;

    public function __construct(RRunnerService $rRunnerService, SessionCountService $sessionCountService) {
        parent::__construct();
        
        $this->rRunnerService = $rRunnerService;
        $this->sessionCountService = $sessionCountService;
    }

    protected function configure() {
        $this->setName("concerto:sessions:log")->setDescription("Log session count.");
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $os = $this->rRunnerService->getOS();
        if ($os !== RRunnerService::OS_LINUX)
            return;

        $count = system("ps -F -C R | grep '" . $this->rRunnerService->getIniFilePath() . "' | wc -l", $retVal);
        if ($retVal === 0) {
            $sc = new SessionCount();
            $sc->setCount($count);
            $this->sessionCountService->save($sc);
        }
    }

}
