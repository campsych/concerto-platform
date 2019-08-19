<?php

namespace Concerto\APIBundle\Service;

use Concerto\TestBundle\Service\ASessionRunnerService;
use Concerto\TestBundle\Service\TestSessionCountService;

class CheckService
{
    private $sessionCountService;
    private $runnerService;

    public function __construct(TestSessionCountService $sessionCountService, ASessionRunnerService $runnerService)
    {
        $this->sessionCountService = $sessionCountService;
        $this->runnerService = $runnerService;
    }

    public function healthCheck()
    {
        return $this->runnerService->healthCheck();
    }

    public function getSessionCount()
    {
        return $this->sessionCountService->getCurrentCount();
    }

    public function promLine($name, $type, $value, $help = null)
    {
        $line = "";
        if ($help !== null) {
            $line .= "# HELP $name $help\n";
        }
        $line .= "# TYPE $name $type\n";

        switch ($type) {
            case "counter":
                $value = number_format($value, 1);
                break;
        }
        $line .= "$name $value\n";
        return $line;
    }
}