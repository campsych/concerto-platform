<?php

namespace Concerto\APIBundle\Repository;

use Doctrine\ORM\EntityRepository;

abstract class AEntityRepository extends EntityRepository {

    public function save($entities) {
        if (is_array($entities)) {
            foreach ($entities as $entity) {
                $this->getEntityManager()->persist($entity);
            }
        } else {
            $this->getEntityManager()->persist($entities);
        }
        $this->getEntityManager()->flush();
    }

    public function deleteById($object_id) {
        $entity = $this->find($object_id);
        if ($entity === null) {
            return false;
        } else {
            $this->getEntityManager()->remove($entity);
            $this->getEntityManager()->flush();
            return true;
        }
    }

    public function filterCollection($filter, $operators, $just_query = false) {
        $i = 0;
        $q = $this->createQueryBuilder("en");
        foreach ($filter as $k => $v) {
            if ($i == 0) {
                $q = $q->where("en.$k ".$operators[$k]." :$k")->setParameter($k, $v);
            } else {
                $q = $q->andWhere("en.$k ".$operators[$k]." :$k")->setParameter($k, $v);
            }
            $i++;
        }
        if($just_query) return $q;
        return $q->getQuery()->getResult();
    }

}
