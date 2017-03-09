<?php

namespace Concerto\TestBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Concerto\TestBundle\Entity\SessionCount;

class SessionCountRepository extends EntityRepository {

    public function save(SessionCount $entity) {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    public function findByFilter($filter) {
        $qb = $this->createQueryBuilder('sc');
        if (array_key_exists("min", $filter)) {
            $qb = $qb->where("sc.created >= :min")->setParameter("min", $filter["min"]);
        }
        if (array_key_exists("max", $filter)) {
            $qb = $qb->where("sc.created <= :max")->setParameter("max", $filter["max"]);
        }
        return $qb->getQuery()->getResult();
    }

}
