<?php

namespace Concerto\PanelBundle\Repository;

/**
 * TestNodePortRepository
 */
class TestNodePortRepository extends AEntityRepository {

    public function findOneByNodeAndVariable($node, $variable) {
        return $this->getEntityManager()->getRepository("ConcertoPanelBundle:TestNodePort")->findOneBy(array("node" => $node, "variable" => $variable));
    }

}
