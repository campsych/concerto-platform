<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Service\LoadBalancerInterface;
use Concerto\PanelBundle\Entity\TestSession;
use Concerto\PanelBundle\Repository\TestSessionRepository;
use Concerto\TestBundle\Service\TestSessionCountService;

class LoadBalancerService implements LoadBalancerInterface {

    private $testSessionRepository;
    private $testNodes;
    private $testSessionCountService;

    public function __construct(TestSessionRepository $testSessionRespository, $testNodes, TestSessionCountService $testSessionCountService) {
        $this->testSessionRepository = $testSessionRespository;
        $this->testNodes = $testNodes;
        $this->testSessionCountService = $testSessionCountService;
    }

    public function getTestNodeById($id) {
        foreach ($this->testNodes as $node) {
            if ($node["id"] == $id)
                return $node;
        }
        return null;
    }

    public function getTestNodeBySession($session_hash) {
        $node_id = null;
        $session = $this->testSessionRepository->findOneBy(array("hash" => $session_hash));
        if ($session) {
            $node_id = $session->getTestNodeId();
        }
        return $this->getTestNodeById($node_id);
    }

    public function getOptimalTestNode() {
        return $this->testNodes[0];
    }

    public function getLocalTestNode() {
        foreach ($this->testNodes as $node) {
            if ($node["local"] == "true") {
                return $node;
            }
        }
        return null;
    }

    public function getTotalSessionCount() {
        return $this->testSessionCountService->getCurrentCount();
    }

    public function authorizeTestNode($calling_node_ip, $node_hash) {
        foreach ($this->testNodes as $node) {
            if ($node_hash == $node["hash"]) {
                return $node;
            }
        }
        return false;
    }

}
