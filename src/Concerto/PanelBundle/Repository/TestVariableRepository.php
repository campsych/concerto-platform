<?php

namespace Concerto\PanelBundle\Repository;

/**
 * TestVariableRepository
 */
class TestVariableRepository extends AEntityRepository {

    public function findByTestAndType($test_id, $type) {
        return $this->findBy(array("test"=> $test_id, "type" => $type));
    }
    
    public function findByTest($test_id) {
        return $this->findBy(array("test" => $test_id));
    }

    public function deleteByTestAndType($test_id, $type) {
        $builder = $this->createQueryBuilder('v')->delete()->where('v.test = :test')->setParameter('test', $test_id)->andWhere("v.type = :type")->setParameter("type", $type);
        return $builder->getQuery()->getResult();
    }

}
