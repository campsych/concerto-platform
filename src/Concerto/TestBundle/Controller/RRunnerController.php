<?php

namespace Concerto\TestBundle\Controller;

use Concerto\TestBundle\Service\RRunnerService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\Templating\EngineInterface;

class RRunnerController {

    private $templating;
    private $rRunnerService;
    private $request;
    private $logger;

    public function __construct(EngineInterface $templating, RRunnerService $rRunnerService, Request $request, LoggerInterface $logger) {
        $this->templating = $templating;
        $this->rRunnerService = $rRunnerService;
        $this->request = $request;
        $this->logger = $logger;
    }

    public function startRAction($session_hash) {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash");

        $client_ip = $this->request->get("client_ip");
        $client_browser = $this->request->get("client_browser");
        $calling_node_ip = $this->request->getClientIp();
        return new Response($this->rRunnerService->startR($this->request->get("test_server_node_hash"), $this->request->get("test_server_node_port"), $session_hash, $this->request->get("values"), $client_ip, $client_browser, $calling_node_ip, $this->request->get("debug")));
    }

}
