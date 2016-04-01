<?php

namespace Concerto\PanelBundle\Repository;

use Concerto\PanelBundle\Repository\AEntityRepository;

/**
 * RDocLibraryRepository
 */
class RDocLibraryRepository extends AEntityRepository {

    public function findOneByName($name) {
        return $this->getEntityManager()->getRepository("ConcertoPanelBundle:RDocLibrary")->findOneBy(array("name" => $name));
    }

}
