<?php

namespace Concerto\PanelBundle\Repository;

/**
 * TestVariableRepository
 */
class TestVariableRepository extends AEntityRepository
{

    public function findByTestAndType($test_id, $type)
    {
        return $this->findBy(array("test" => $test_id, "type" => $type));
    }
}
