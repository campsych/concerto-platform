<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Entity\TestSessionLog;
use Concerto\PanelBundle\Repository\TestSessionLogRepository;
use Concerto\PanelBundle\Security\ObjectVoter;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class TestSessionLogService extends ASectionService
{

    private $testService;

    public function __construct(TestSessionLogRepository $repository, TestService $testService, AuthorizationCheckerInterface $securityAuthorizationChecker)
    {
        parent::__construct($repository, $securityAuthorizationChecker);

        $this->testService = $testService;
    }

    public function get($object_id, $createNew = false, $secure = true)
    {
        $object = parent::get($object_id, $createNew, $secure);
        if ($createNew && $object === null) {
            $object = new TestSessionLog();
        }
        return $object;
    }

    public function getByTest($test_id)
    {
        return $this->authorizeCollection($this->repository->findByTest($test_id));
    }

    public function delete($object_ids, $secure = true)
    {
        $object_ids = explode(",", $object_ids);

        $result = array();
        foreach ($object_ids as $object_id) {
            $object = $this->get($object_id, false, $secure);
            if ($object === null)
                continue;
            $this->repository->delete($object);
            array_push($result, array("object" => $object, "errors" => array()));
        }
        return $result;
    }

    public function clear($test_id)
    {
        $test = parent::authorizeObject($this->testService->get($test_id));
        if ($test)
            $this->repository->deleteByTest($test_id);
        return array("errors" => array());
    }

    public function authorizeObject($object)
    {
        if (!self::$securityOn)
            return $object;
        if ($object && $this->securityAuthorizationChecker->isGranted(ObjectVoter::ATTR_ACCESS, $object->getTest()))
            return $object;
        return null;
    }

}
