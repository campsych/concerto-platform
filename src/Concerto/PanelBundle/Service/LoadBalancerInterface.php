<?php

namespace Concerto\PanelBundle\Service;

interface LoadBalancerInterface {

    public function getTestNodeById($id);

    public function getTestNodeBySession($session_hash);

    public function getOptimalTestNode();
    
    public function getLocalTestNode();

    public function getTotalSessionCount();
    
    public function authorizeTestNode($calling_node_ip, $node_hash);
}
