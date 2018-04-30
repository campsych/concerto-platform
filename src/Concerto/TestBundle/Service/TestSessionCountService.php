<?php

namespace Concerto\TestBundle\Service;

use Concerto\PanelBundle\Repository\TestSessionRepository;
use Concerto\TestBundle\Entity\TestSessionCount;
use Concerto\TestBundle\Repository\TestSessionCountRepository;

class TestSessionCountService
{
    private $sessionRepo;
    private $sessionCountRepo;
    private $administration;

    public function __construct(TestSessionCountRepository $sessionCountRepo, TestSessionRepository $sessionRepo, $administration)
    {
        $this->sessionCountRepo = $sessionCountRepo;
        $this->sessionRepo = $sessionRepo;
        $this->administration = $administration;
    }

    public function save(TestSessionCount $entity)
    {
        $this->sessionCountRepo->save($entity);
    }

    public function getCollection($filter)
    {
        return $this->sessionCountRepo->findByFilter($filter);
    }

    public function getLastRecordedCount()
    {
        $last = $this->sessionCountRepo->findLast();
        if ($last === null) {
            return 0;
        }
        return $last->getCount();
    }

    public function getCurrentCount()
    {
        return $this->sessionRepo->getActiveSessionsCount($this->administration["internal"]["session_count_idle_limit"]);
    }

    public function updateCountRecord($offset = 0)
    {
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

    public function clear()
    {
        $this->sessionCountRepo->deleteAll();
    }

}
