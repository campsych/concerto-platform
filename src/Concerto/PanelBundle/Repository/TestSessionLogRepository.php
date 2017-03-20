<?php

namespace Concerto\PanelBundle\Repository;

use DateTime;

/**
 * TestSessionLogRepository
 */
class TestSessionLogRepository extends AEntityRepository {

    public function deleteByTest($test_id) {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete("Concerto\PanelBundle\Entity\TestSessionLog", "tsl")->where("tsl.test = :ti")->setParameter("ti", $test_id)->getQuery()->execute();
    }
    
    public function findAllNewerThan($time) {
        $dt = new DateTime();
        $dt->setTimestamp($time);
        $builder = $this->getEntityManager()->getRepository("ConcertoPanelBundle:TestSessionLog")->createQueryBuilder( 'tsl' );
        $builder->where("tsl.created > :tslc")->setParameter("tslc", $dt);
        return $builder->getQuery()->execute();
    }

    public function findByTest($test_id) {
    
        $builder = $this->getEntityManager()->getRepository("ConcertoPanelBundle:TestSessionLog")->createQueryBuilder( 'tsl' );
        $builder->where("tsl.test = :ti")->setParameter("ti", $test_id);
        
        return $builder->getQuery()->execute();
    
    }
} 
