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
}
