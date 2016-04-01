<?php

namespace Concerto\PanelBundle\Repository;

/**
 * RoleRepository
 */
class RoleRepository extends AEntityRepository {

    public function findOneByRole($role) {
        return $this->getEntityManager()->getRepository("ConcertoPanelBundle:Role")->findOneBy(array("role" => $role));
    }

}
