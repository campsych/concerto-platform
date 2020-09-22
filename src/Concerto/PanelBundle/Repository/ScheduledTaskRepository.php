<?php

namespace Concerto\PanelBundle\Repository;

use Concerto\PanelBundle\Entity\ScheduledTask;

/**
 * ScheduledTaskRepository
 */
class ScheduledTaskRepository extends AEntityRepository
{
    const GIT_TYPES = [
        ScheduledTask::TYPE_GIT_PULL,
        ScheduledTask::TYPE_GIT_ENABLE,
        ScheduledTask::TYPE_GIT_UPDATE,
        ScheduledTask::TYPE_GIT_RESET
    ];

    public function findAllPending()
    {
        return $this->findBy(array("status" => ScheduledTask::STATUS_PENDING));
    }

    public function findAllOngoing()
    {
        return $this->findBy(array("status" => ScheduledTask::STATUS_ONGOING));
    }

    public function cancelPending()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        return $qb->update("Concerto\PanelBundle\Entity\ScheduledTask", "st")->set("st.status", ScheduledTask::STATUS_CANCELED)->where("st.status = :pending_status")->setParameter("pending_status", ScheduledTask::STATUS_PENDING)->getQuery()->execute();
    }

    public function findLatestGit()
    {
        return $this->findOneBy(array("type" => self::GIT_TYPES), array("id" => "DESC"));
    }
}
