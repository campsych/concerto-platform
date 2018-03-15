<?php

namespace Concerto\TestBundle\Service;

use Concerto\TestBundle\Entity\TestSessionCount;
use Concerto\TestBundle\Service\RRunnerService;
use Concerto\TestBundle\Repository\TestSessionCountRepository;

class TestSessionCountService {

    private $sessionCountRepo;

    public function __construct(TestSessionCountRepository $sessionCountRepo) {
        $this->sessionCountRepo = $sessionCountRepo;
    }
    
    //TODO proper OS detection
    public function getOS() {
        if (strpos(strtolower(PHP_OS), "win") !== false) {
            return RRunnerService::OS_WIN;
        } else {
            return RRunnerService::OS_LINUX;
        }
    }

    public function save(TestSessionCount $entity) {
        $this->sessionCountRepo->save($entity);
    }

    public function getCollection($filter) {
        return $this->sessionCountRepo->findByFilter($filter);
    }

    public function getLastRecordedCount() {
        $last = $this->sessionCountRepo->findLast();
        if ($last === null) {
            return 0;
        }
        return $last->getCount();
    }

    public function getCurrentCount() {
        if ($this->getOS() !== RRunnerService::OS_LINUX)
            return false;

        $count = exec("ps -F -C R --width 500 | grep '/Resources/R/standalone.R ' | wc -l", $arr, $retVal);
        if ($retVal === 0) {
            return (int) $count;
        }
        return false;
    }

    public function updateCountRecord($offset = 0) {
        $last = $this->getLastRecordedCount();
        $now = $this->getCurrentCount();
        if ($now === false)
            return;
        $now += $offset;

        if ($last !== $now) {
            $sc = new TestSessionCount();
            $sc->setCount($now);
            $this->save($sc);
        }
    }

    public function clear() {
        $this->sessionCountRepo->deleteAll();
    }

}
