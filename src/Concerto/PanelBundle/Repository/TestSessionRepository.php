<?php

namespace Concerto\PanelBundle\Repository;

use Concerto\PanelBundle\Entity\TestSession;
use Doctrine\DBAL\Types\Type;

/**
 * TestSessionRepository
 */
class TestSessionRepository extends AEntityRepository
{
    public function getActiveSessionsCount($idleLimit)
    {
        $dt = new \DateTime();
        $di = new \DateInterval('PT' . $idleLimit . 'S');
        $dt->sub($di);

        $builder = $this->getEntityManager()->getConnection()->createQueryBuilder()->select('count(ts.id)')->from("TestSession", "ts");
        $builder->where("ts.status = :status")->setParameter('status', TestSession::STATUS_RUNNING);
        $builder->andWhere("ts.updated >= :updated")->setParameter('updated', $dt, Type::DATETIME);

        return (int)$builder->execute()->fetchColumn(0);
    }

    public function getUpdatedAgo($id)
    {
        $select = "TIME_TO_SEC(TIMEDIFF(CURRENT_TIMESTAMP, ts.updated))";
        if ($this->getEntityManager()->getConnection()->getDriver()->getName() == "oci8") {
            $select = "ROUND((CAST(SYSTIMESTAMP AS DATE) - CAST(ts.updated AS DATE)) * 86400)";
        }

        $builder = $this->getEntityManager()->getConnection()->createQueryBuilder()->select($select)->from("TestSession", "ts");
        $builder->where("ts.id = :id")->setParameter('id', $id);

        return (int)$builder->execute()->fetchOne();
    }
}
