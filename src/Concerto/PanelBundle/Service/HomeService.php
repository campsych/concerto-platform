<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Repository\TestRepository;
use Concerto\PanelBundle\Entity\Test;

class HomeService {

    private $testRepository;

    public function __construct(TestRepository $testRepository) {
        $this->testRepository = $testRepository;
    }

    public function getFeaturedTests() {
        return $this->testRepository->findByVisibility(Test::VISIBILITY_FEATURED);
    }

}
