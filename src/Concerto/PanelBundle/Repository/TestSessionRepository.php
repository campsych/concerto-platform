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
}
