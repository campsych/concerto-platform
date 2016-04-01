<?php

namespace Concerto\PanelBundle\Repository;

/**
 * TestNodeConnectionRepository
 */
class TestNodeConnectionRepository extends AEntityRepository {

    public function findByFlowTest($flowTest) {
        return $this->getEntityManager()->getRepository("ConcertoPanelBundle:TestNodeConnection")->findBy(array("flowTest" => $flowTest));
    }

    public function deleteAutomatic($sourceNode, $destinationNode) {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete("Concerto\PanelBundle\Entity\TestNodeConnection", "tnc")->where("tnc.sourceNode = :sn")->setParameter("sn", $sourceNode)->andWhere("tnc.destinationNode = :dn")->setParameter("dn", $destinationNode)->andWhere("tnc.automatic = :au")->setParameter("au", true)->getQuery()->execute();
    }

}
