<?php

namespace Concerto\TestBundle\Service;

use Concerto\TestBundle\Entity\SessionCount;
use Concerto\TestBundle\Repository\SessionCountRepository;

class SessionCountService {
    
    private $sessionCountRepo;
    
    public function __construct(SessionCountRepository $sessionCountRepo) {
        $this->sessionCountRepo = $sessionCountRepo;
    }
    
    public function save(SessionCount $entity){
        $this->sessionCountRepo->save($entity);
    }
}