<?php

namespace Concerto\PanelBundle\Repository;

use Concerto\PanelBundle\Entity\User;

/**
 * UserRepository
 */
class UserRepository extends AEntityRepository {

    public function findAllExcept(User $user) {
        $query = $this->getEntityManager()->createQuery(
                        'SELECT u
            FROM ConcertoPanelBundle:User u
            WHERE u.id != :id'
                )->setParameter('id', $user->getId());
        return $query->getResult();
    }

}
