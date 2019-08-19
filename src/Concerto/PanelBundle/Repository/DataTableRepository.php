<?php

namespace Concerto\PanelBundle\Repository;

/**
 * DataTableRepository
 */
class DataTableRepository extends AEntityRepository
{
    public function findOneByName($name)
    {
        return $this->getEntityManager()->getRepository("ConcertoPanelBundle:DataTable")->findOneBy(array("name" => $name));
    }

    public function findDirectlyLocked()
    {
        $qb = $this->getEntityManager()->createQueryBuilder()->select("dt")->from("Concerto\PanelBundle\Entity\DataTable", "dt")->where("dt.directLockBy IS NOT NULL");
        return $qb->getQuery()->getResult();
    }
}