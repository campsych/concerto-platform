<?php

namespace Concerto\PanelBundle\Repository;

/**
 * TestWizardParamRepository
 */
class TestWizardParamRepository extends AEntityRepository {

    public function deleteByTestWizard($wizard_id) {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete("Concerto\PanelBundle\Entity\TestWizardParam", "tsl")->where("tsl.wizard = :ti")->setParameter("ti", $wizard_id)->getQuery()->execute();
    }

    public function findByTestWizard($wizard) {
        return $this->getEntityManager()->getRepository("ConcertoPanelBundle:TestWizardParam")->findBy(array("wizard" => $wizard));
    }

    public function findByTestWizardAndType($wizard, $type) {
        return $this->getEntityManager()->getRepository("ConcertoPanelBundle:TestWizardParam")->findBy(array("wizard" => $wizard, "type" => $type));
    }

}
