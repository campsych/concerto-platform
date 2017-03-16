<?php

namespace Concerto\TestBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Concerto\TestBundle\Entity\TestSessionCount;
use DateTime;

class TestSessionCountRepository extends EntityRepository {

    public function save(TestSessionCount $entity) {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    public function findByFilter($filter) {
        $qb = $this->createQueryBuilder('sc');
        if (array_key_exists("min", $filter)) {
            $min = new DateTime();
            $min->setTimestamp((int) $filter["min"]);
            $qb = $qb->where("sc.created >= :min")->setParameter("min", $min);
        }
        if (array_key_exists("max", $filter)) {
            $max = new DateTime();
            $max->setTimestamp((int) $filter["max"]);
            $qb = $qb->andWhere("sc.created <= :max")->setParameter("max", $max);
        }
        return $qb->getQuery()->getResult();
    }

    public function findLast() {
        return $this->findOneBy(array(), array('id' => 'DESC'));
    }

    public function deleteAll() {
        return $this->getEntityManager()->createQueryBuilder()->delete("Concerto\TestBundle\Entity\TestSessionCount", "sc")->getQuery()->execute();
    }

}
