<?php

namespace Concerto\PanelBundle\Repository;

/**
 * TestWizardRepository
 */
class TestWizardRepository extends AEntityRepository
{
    public function findDirectlyLocked()
    {
        $qb = $this->getEntityManager()->createQueryBuilder()->select("tw")->from("Concerto\PanelBundle\Entity\TestWizard", "tw")->where("tw.directLockBy IS NOT NULL");
        return $qb->getQuery()->getResult();
    }
}
