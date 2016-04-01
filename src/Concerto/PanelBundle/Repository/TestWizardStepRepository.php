<?php

namespace Concerto\PanelBundle\Repository;

/**
 * TestWizardStepRepository
 */
class TestWizardStepRepository extends AEntityRepository {

    public function deleteByTestWizard($wizard_id) {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete("Concerto\PanelBundle\Entity\TestWizardStep", "tsl")->where("tsl.wizard = :ti")->setParameter("ti", $wizard_id)->getQuery()->execute();
    }

    public function findByTestWizard($wizard) {
        return $this->getEntityManager()->getRepository("ConcertoPanelBundle:TestWizardStep")->findBy(array("wizard" => $wizard));
    }
}
