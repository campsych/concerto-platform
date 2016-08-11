<?php

namespace Concerto\PanelBundle\Repository;

/**
 * TestWizardRepository
 */
class TestWizardRepository extends AEntityRepository {
    public function findOneByName($name) {
        return $this->getEntityManager()->getRepository("ConcertoPanelBundle:TestWizard")->findOneBy(array("name" => $name));
    }
}
