<?php

namespace Concerto\PanelBundle\Repository;

use Concerto\PanelBundle\Entity\Test;

/**
 * TestNodeConnectionRepository
 */
class TestNodeConnectionRepository extends AEntityRepository
{

    public function findByPorts($sourcePort, $destinationPort)
    {
        return $this->getEntityManager()->getRepository("ConcertoPanelBundle:TestNodeConnection")->findOneBy(array("sourcePort" => $sourcePort, "destinationPort" => $destinationPort));
    }

    public function findByNodes($sourceNode, $destinationNode)
    {
        return $this->getEntityManager()->getRepository("ConcertoPanelBundle:TestNodeConnection")->findBy(array("sourceNode" => $sourceNode, "destinationNode" => $destinationNode));
    }

    public function deleteAutomatic($sourceNode, $destinationNode)
    {
        $this->delete($this->findBy(array(
            "sourceNode" => $sourceNode,
            "destinationNode" => $destinationNode,
            "automatic" => true
        )));
    }
}
