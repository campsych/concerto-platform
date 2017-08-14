<?php

namespace Concerto\PanelBundle\Service;

use Psr\Log\LoggerInterface;
use Concerto\PanelBundle\Service\TestSessionService;
use Concerto\PanelBundle\Service\LoadBalancerInterface;

class TestRunnerService {

    private $panelNodes;
    private $logger;
    private $environment;
    public $loadBalancerService;
    private $sessionService;

    public function __construct($environment, $panelNodes, LoggerInterface $logger, TestSessionService $sessionService, LoadBalancerInterface $loadBalancerService) {
        $this->environment = $environment;
        $this->panelNodes = $panelNodes;
        $this->logger = $logger;
        $this->sessionService = $sessionService;
        $this->loadBalancerService = $loadBalancerService;
    }

    public function startNewSession($test_slug, $node_id, $params, $client_ip, $client_browser, $debug) {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $test_slug, $node_id, $params, $client_ip, $client_browser, $debug");

        $panel_node = $this->getPanelNodeById($node_id);
        $test_node = $this->loadBalancerService->getOptimalTestNode();

        $response = $this->sessionService->startNewSession($test_node["hash"], $test_slug, $params, $client_ip, $client_browser, false, $debug);
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - RESPONSE: $response");
        return $response;
    }

    public function submitToSession($session_hash, $node_id, $values, $client_ip, $client_browser, $time) {
        $values = json_encode($values);
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $node_id, $values, $client_ip, $client_browser");

        $response = $this->sessionService->submit($session_hash, $values, $client_ip, $client_browser, false, $time);
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - RESPONSE: $response");
        return $response;
    }

    public function keepAliveSession($session_hash, $node_id, $client_ip) {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $node_id, $client_ip");

        $response = $this->sessionService->keepAlive($session_hash, $client_ip, false);
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - RESPONSE: $response");
        return $response;
    }

    public function resultsFromSession($session_hash, $node_id) {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $node_id");

        $response = $this->sessionService->results($session_hash, false);
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - RESPONSE: $response");
        return $response;
    }

    public function getPanelNodeById($node_id) {
        foreach ($this->panelNodes as $node) {
            if ($node["id"] == $node_id) {
                return $node;
            }
        }
        return $this->panelNodes[0];
    }

    public function isBrowserValid($user_agent) {
        if (preg_match('/(?i)msie [1-8]\./', $user_agent)) {
            return false;
        } else {
            return true;
        }
    }

    public function uploadFile($session_hash, $files, $name) {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $name");

        $response = $this->sessionService->uploadFile($session_hash, false, $files, $name);
        $response = json_encode($response);
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - RESPONSE: $response");
        return $response;
    }

    public function logError($session_hash, $node_id, $error, $type) {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $node_id, $type");
        $this->logger->info($error);

        $response = $this->sessionService->logError($session_hash, false, $error, $type);
        $response = json_encode($response);
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - RESPONSE: $response");
        return $response;
    }

}
