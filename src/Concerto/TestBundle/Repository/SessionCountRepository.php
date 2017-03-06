<?php

namespace Concerto\TestBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Concerto\TestBundle\Entity\SessionCount;

class SessionCountRepository extends EntityRepository {

    public function save(SessionCount $entity) {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

}
