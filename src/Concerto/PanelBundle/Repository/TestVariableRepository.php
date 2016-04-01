<?php

namespace Concerto\PanelBundle\Repository;

use Concerto\PanelBundle\Repository\AEntityRepository;

/**
 * TestVariableRepository
 */
class TestVariableRepository extends AEntityRepository {

    public function findByTestAndType($test_id, $type) {
        $builder = $this->createQueryBuilder('v')->where("v.test = :test")->setParameter("test", $test_id)->andWhere("v.type = :type")->setParameter("type", $type);
        return $builder->getQuery()->getResult();
    }
    
    public function findByTest($test_id) {
        $builder = $this->createQueryBuilder('v')->where("v.test = :test")->setParameter("test", $test_id);
        return $builder->getQuery()->getResult();
    }

    public function deleteByTestAndType($test_id, $type) {
        $builder = $this->createQueryBuilder('v')->delete()->where('v.test = :test')->setParameter('test', $test_id)->andWhere("v.type = :type")->setParameter("type", $type);
        return $builder->getQuery()->getResult();
    }

}
