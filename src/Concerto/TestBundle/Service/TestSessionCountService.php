<?php

namespace Concerto\TestBundle\Service;

use Concerto\TestBundle\Entity\TestSessionCount;
use Concerto\TestBundle\Repository\TestSessionCountRepository;

class TestSessionCountService
{
    const OS_WIN = 0;
    const OS_LINUX = 1;

    private $sessionCountRepo;

    public function __construct(TestSessionCountRepository $sessionCountRepo)
    {
        $this->sessionCountRepo = $sessionCountRepo;
    }

    //TODO proper OS detection
    public function getOS()
    {
        if (strpos(strtolower(PHP_OS), "win") !== false) {
            return self::OS_WIN;
        } else {
            return self::OS_LINUX;
        }
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
        if ($this->getOS() !== self::OS_LINUX)
            return false;

        $sum = 0;
        $count = exec("ps -F -C R --width 500 | grep '/Resources/R/standalone.R ' | wc -l", $arr1, $retVal1);
        if ($retVal1 === 0) {
            $sum += (int)$count;
        }
        $count = exec("ps -F -C R --width 500 | grep 'forker.R' | wc -l", $arr2, $retVal2);
        if ($retVal2 === 0) {
            $sum += max((int)$count - 1, 0);
        }
        return $sum;
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
