<?php

namespace Concerto\PanelBundle\Repository;

use Concerto\PanelBundle\Entity\ScheduledTask;

/**
 * ScheduledTaskRepository
 */
class ScheduledTaskRepository extends AEntityRepository {

    public function findAllPending() {
        return $this->getEntityManager()->getRepository("ConcertoPanelBundle:ScheduledTask")->findBy(array("status" => ScheduledTask::STATUS_PENDING));
    }

    public function findAllOngoing() {
        return $this->getEntityManager()->getRepository("ConcertoPanelBundle:ScheduledTask")->findBy(array("status" => ScheduledTask::STATUS_ONGOING));
    }

    public function cancelPending() {
        $qb = $this->getEntityManager()->createQueryBuilder();
        return $qb->update("Concerto\PanelBundle\Entity\ScheduledTask", "st")->set("st.status", ScheduledTask::STATUS_CANCELED)->where("st.status = :pending_status")->setParameter("pending_status", ScheduledTask::STATUS_PENDING)->getQuery()->execute();
    }

}
