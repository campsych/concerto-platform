<?php

namespace Concerto\PanelBundle\Repository;

use Concerto\PanelBundle\Entity\Test;

/**
 * TestNodeRepository
 */
class TestNodeRepository extends AEntityRepository
{

    public function deleteByTest(Test $test)
    {
        $this->delete($test->getNodes()->toArray());
    }

}
