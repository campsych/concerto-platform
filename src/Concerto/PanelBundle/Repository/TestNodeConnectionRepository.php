<?php

namespace Concerto\PanelBundle\Repository;

/**
 * TestNodeConnectionRepository
 */
class TestNodeConnectionRepository extends AEntityRepository
{

    public function findByNodes($sourceNode, $destinationNode)
    {
        return $this->getEntityManager()->getRepository("ConcertoPanelBundle:TestNodeConnection")->findBy(array("sourceNode" => $sourceNode, "destinationNode" => $destinationNode));
    }
}
