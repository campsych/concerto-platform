<?php

namespace Concerto\PanelBundle\Repository;

use Doctrine\ORM\EntityRepository;

abstract class AEntityRepository extends EntityRepository {

    public function refresh($entity) {
        $this->getEntityManager()->refresh($entity);
    }

    public function flush() {
        $this->getEntityManager()->flush();
    }

    public function clear() {
        $this->getEntityManager()->clear();
    }

    public function save($entities, $flush = true) {
        if (is_array($entities)) {
            foreach ($entities as $entity) {
                $this->getEntityManager()->persist($entity);
            }
        } else {
            $this->getEntityManager()->persist($entities);
            if ($flush)
                $this->getEntityManager()->flush($entities);
            return;
        }
        if ($flush)
            $this->getEntityManager()->flush();
    }

    public function delete($entities, $flush = true) {
        if (is_array($entities)) {
            foreach ($entities as $entity) {
                $this->getEntityManager()->remove($entity);
            }
        } else {
            $this->getEntityManager()->remove($entities);
            if ($flush)
                $this->getEntityManager()->flush($entities);
            return;
        }
        if ($flush)
            $this->getEntityManager()->flush();
    }

    public function deleteById($object_ids, $flush = true) {
        foreach ($object_ids as $object_id) {
            $entity = $this->find($object_id);
            $this->getEntityManager()->remove($entity);
        }
        if ($flush)
            $this->getEntityManager()->flush();
    }

    public function deleteAll($flush = true) {
        foreach ($this->findAll() as $object) {
            $this->getEntityManager()->remove($object);
        }
        if ($flush)
            $this->getEntityManager()->flush();
    }

}
