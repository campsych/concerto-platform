<?php

namespace Concerto\PanelBundle\Repository;

use Concerto\PanelBundle\Repository\AEntityRepository;

/**
 * DataTableRepository
 */
class DataTableRepository extends AEntityRepository {
    public function findOneByName($name) {
        return $this->getEntityManager()->getRepository("ConcertoPanelBundle:DataTable")->findOneBy(array("name" => $name));
    }
}

?>