<?php

namespace Concerto\PanelBundle\Repository;

/**
 * TestNodeRepository
 */
class TestNodeRepository extends AEntityRepository {

    public function findByFlowTest($flowTest) {
        return $this->getEntityManager()->getRepository("ConcertoPanelBundle:TestNode")->findBy(array("flowTest" => $flowTest));
    }

}
