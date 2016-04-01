<?php

namespace Concerto\PanelBundle\Repository;

use Concerto\PanelBundle\Repository\AEntityRepository;

/**
 * RDocFunctionRepository
 */
class RDocFunctionRepository extends AEntityRepository {

    public function findBySimilarName($name) {
        return $this->createQueryBuilder('f')->where("f.name LIKE :name")->setParameter("name", "%$name%")->getQuery()->getResult();
    }

    public function findOneByName($name) {
        $result = $this->createQueryBuilder('f')->where("f.name = :name")->setParameter("name", "$name")->getQuery()->getResult();
        if (count($result) > 0) {
            return $result[0];
        } else {
            return null;
        }
    }
}
