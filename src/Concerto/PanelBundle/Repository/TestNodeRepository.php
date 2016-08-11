<?php

namespace Concerto\PanelBundle\Repository;

/**
 * TestNodeRepository
 */
class TestNodeRepository extends AEntityRepository {

    public function findByFlowTest($flowTest) {
        return $this->getEntityManager()->getRepository("ConcertoPanelBundle:TestNode")->findBy(array("flowTest" => $flowTest));
    }

    public function deleteByTest($test) {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete("Concerto\PanelBundle\Entity\TestNode", "tn")->where("tn.flowTest = :ft")->setParameter("ft", $test)->getQuery()->execute();
    }

}
