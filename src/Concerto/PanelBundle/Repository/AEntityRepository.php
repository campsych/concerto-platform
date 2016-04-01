<?php

namespace Concerto\PanelBundle\Repository;

use Doctrine\ORM\EntityRepository;

abstract class AEntityRepository extends EntityRepository {
    
    public function refresh($entity){
        $this->getEntityManager()->refresh($entity);
    }

    public function save($entities) {
        if (is_array($entities)) {
            foreach ($entities as $entity) {
                $this->getEntityManager()->persist($entity);
            }
        } else {
            $this->getEntityManager()->persist($entities);
        }
        $this->getEntityManager()->flush();
    }

    public function delete($entities) {
        if (is_array($entities)) {
            foreach ($entities as $entity) {
                $this->getEntityManager()->remove($entity);
            }
        } else {
            $this->getEntityManager()->remove($entities);
        }
        $this->getEntityManager()->flush();
    }

    public function deleteById($object_ids) {
        foreach ($object_ids as $object_id) {
            $entity = $this->find($object_id);
            $this->getEntityManager()->remove($entity);
        }
        $this->getEntityManager()->flush();
    }

    public function deleteAll() {
        foreach ($this->findAll() as $object) {
            $this->getEntityManager()->remove($object);
        }
        $this->getEntityManager()->flush();
    }

}
