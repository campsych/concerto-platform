<?php

namespace Concerto\TestBundle\Service;

use Concerto\PanelBundle\Entity\TestSession;

class PersistantSessionRunnerService extends ASessionRunnerService
{

    public function startNew(TestSession $session, $params, $client_ip, $client_browser, $debug = false)
    {
        // TODO: Implement startNew() method.
    }

    public function submit(TestSession $session, $values, $client_ip, $client_browser, $time = null)
    {
        // TODO: Implement submit() method.
    }
}