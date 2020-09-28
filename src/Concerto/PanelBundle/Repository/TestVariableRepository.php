<?php

namespace Concerto\PanelBundle\Repository;

/**
 * TestVariableRepository
 */
class TestVariableRepository extends AEntityRepository
{
    public function findByTest($test_id)
    {
        $test = $this->getEntityManager()->getRepository("ConcertoPanelBundle:Test")->find($test_id);
        return $test->getVariables();
    }

    public function findByTestAndType($test_id, $type)
    {
        $test = $this->getEntityManager()->getRepository("ConcertoPanelBundle:Test")->find($test_id);
        return $test->getVariablesByType($type);
    }
}
