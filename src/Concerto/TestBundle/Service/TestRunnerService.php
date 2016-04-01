<?php

namespace Concerto\TestBundle\Service;

use Psr\Log\LoggerInterface;
use Concerto\PanelBundle\Service\TestSessionService;

class TestRunnerService {

    private $nodes;
    private $logger;
    private $environment;
    private $sessionService;

    public function __construct($environment, $nodes, LoggerInterface $logger, TestSessionService $sessionService) {
        $this->environment = $environment;
        $this->nodes = $nodes;
        $this->logger = $logger;
        $this->sessionService = $sessionService;
    }

    public function startNewSession($test_slug, $node_id, $params, $client_ip, $client_browser) {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $test_slug, $node_id, $params, $client_ip, $client_browser");

        $test_server_node = $this->getNodeById($node_id);
        $r_server_node = $this->getRServerNode($test_server_node);

        if ($test_server_node["id"] != "local") {
            $url = $test_server_node["protocol"] . "://" . $test_server_node["host"] . $test_server_node["dir"] . ($this->environment == "prod" ? "" : "app_dev.php/") . "TestSession/Test/$test_slug/start/" . urlencode($params);
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - remote node URL : " . $url);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "r_server_node_hash=" . urlencode($r_server_node["hash"]) . "&client_ip=" . urlencode($client_ip) . "&client_browser=" . urlencode($client_browser));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($ch);
            curl_close($ch);
            return $response;
        } else {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - local node");
            $response = $this->sessionService->startNewSession($r_server_node["hash"], $test_slug, $params, $client_ip, $client_browser, false, false);
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - RESPONSE: $response");
            return $response;
        }
    }

    public function submitToSession($session_hash, $node_id, $values, $client_ip, $client_browser) {
        $values = json_encode($values);
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $node_id, $values, $client_ip, $client_browser");

        $test_server_node = $this->getNodeById($node_id);
        $r_server_node = $this->getRServerNode($test_server_node);

        if ($test_server_node["id"] != "local") {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - remote node");
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $test_server_node["protocol"] . "://" . $test_server_node["host"] . $test_server_node["dir"] . ($this->environment == "prod" ? "" : "app_dev.php/") . "TestSession/$session_hash/submit");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "r_server_node_hash=" . urlencode($r_server_node["hash"]) . "&values=" . urlencode($values) . "&client_ip=" . urlencode($client_ip) . "&client_browser=" . urlencode($client_browser));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($ch);
            curl_close($ch);
            return $response;
        } else {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - local node");
            $response = $this->sessionService->submit($r_server_node["hash"], $session_hash, $values, $client_ip, $client_browser, false);
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - RESPONSE: $response");
            return $response;
        }
    }
    
    public function keepAliveSession($session_hash, $node_id, $client_ip) {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $node_id, $client_ip");

        $test_server_node = $this->getNodeById($node_id);
        $r_server_node = $this->getRServerNode($test_server_node);

        if ($test_server_node["id"] != "local") {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - remote node");
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $test_server_node["protocol"] . "://" . $test_server_node["host"] . $test_server_node["dir"] . ($this->environment == "prod" ? "" : "app_dev.php/") . "TestSession/$session_hash/keepalive");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "r_server_node_hash=" . urlencode($r_server_node["hash"]) . "&client_ip=" . urlencode($client_ip));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($ch);
            curl_close($ch);
            return $response;
        } else {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - local node");
            $response = $this->sessionService->keepAlive($r_server_node["hash"], $session_hash, $client_ip, false);
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - RESPONSE: $response");
            return $response;
        }
    }

    public function resumeSession($session_hash, $node_id) {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $node_id");

        $test_server_node = $this->getNodeById($node_id);
        $r_server_node = $this->getRServerNode($test_server_node);

        if ($test_server_node["id"] != "local") {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - remote node");
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $test_server_node["protocol"] . "://" . $test_server_node["host"] . $test_server_node["dir"] . ($this->environment == "prod" ? "" : "app_dev.php/") . "TestSession/$session_hash/resume");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "r_server_node_hash=" . urlencode($r_server_node["hash"]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($ch);
            curl_close($ch);
            return $response;
        } else {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - local node");
            $response = $this->sessionService->resume($r_server_node["hash"], $session_hash, false);
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - RESPONSE: $response");
            return $response;
        }
    }

    public function resultsFromSession($session_hash, $node_id) {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $node_id");

        $test_server_node = $this->getNodeById($node_id);
        $r_server_node = $this->getRServerNode($test_server_node);

        if ($test_server_node["id"] != "local") {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - remote node");
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $test_server_node["protocol"] . "://" . $test_server_node["host"] . $test_server_node["dir"] . ($this->environment == "prod" ? "" : "app_dev.php/") . "TestSession/$session_hash/results");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "r_server_node_hash=" . urlencode($r_server_node["hash"]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($ch);
            curl_close($ch);
            return $response;
        } else {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - local node");
            $response = $this->sessionService->results($r_server_node["hash"], $session_hash, false);
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - RESPONSE: $response");
            return $response;
        }
    }

    private function getRServerNode($test_server_node) {
        return $test_server_node;
    }

    public function getNodeById($node_id) {
        foreach ($this->nodes as $node) {
            if ($node["id"] == $node_id) {
                return $node;
            }
        }
        return $this->nodes[0];
    }

    public function isBrowserValid($user_agent) {
        if (preg_match('/(?i)msie [1-8]\./', $user_agent)) {
            return false;
        } else {
            return true;
        }
    }

}
