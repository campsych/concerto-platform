<?php

namespace Concerto\TestBundle\Service;

use Concerto\TestBundle\Entity\TestSessionCount;
use Concerto\TestBundle\Service\RRunnerService;
use Concerto\TestBundle\Repository\TestSessionCountRepository;

class TestSessionCountService {

    private $sessionCountRepo;
    private $rRunnerService;

    public function __construct(TestSessionCountRepository $sessionCountRepo, RRunnerService $rRunnerService) {
        $this->sessionCountRepo = $sessionCountRepo;
        $this->rRunnerService = $rRunnerService;
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
        $os = $this->rRunnerService->getOS();
        if ($os !== RRunnerService::OS_LINUX)
            return false;

        $count = system("ps -F -C R | grep '" . $this->rRunnerService->getIniFilePath() . "' | wc -l", $retVal);
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

}
