<?php

namespace Concerto\PanelBundle\Repository;

use Concerto\PanelBundle\Repository\AEntityRepository;

/**
 * ViewTemplateRepository
 */
class ViewTemplateRepository extends AEntityRepository {

    public function findOneByName($name) {
        return $this->getEntityManager()->getRepository("ConcertoPanelBundle:ViewTemplate")->findOneBy(array("name" => $name));
    }

}
