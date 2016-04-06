<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Repository\AEntityRepository;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Concerto\PanelBundle\Security\ObjectVoter;

abstract class ASectionService {

    protected $repository;
    protected $securityAuthorizationChecker;

    public function __construct(AEntityRepository $repository, AuthorizationChecker $securityAuthorizationChecker) {
        $this->repository = $repository;
        $this->securityAuthorizationChecker = $securityAuthorizationChecker;
    }

    public function getAll() {
        return $this->authorizeCollection($this->repository->findAll());
    }

    public function authorizeObject($object) {
        if ($this->securityAuthorizationChecker->isGranted(ObjectVoter::ATTR_ACCESS, $object))
            return $object;
        return null;
    }

    public function authorizeCollection($collection) {
        $result = array();
        foreach ($collection as $object) {
            if ($this->securityAuthorizationChecker->isGranted(ObjectVoter::ATTR_ACCESS, $object))
                array_push($result, $object);
        }
        return $result;
    }

    public function get($object_id, $createNew = false, $secure = true) {
        if (!$object_id) {
            return null;
        }
        $object = $this->repository->find($object_id);
        if ($secure) {
            $object = $this->authorizeObject($object);
        }
        return $object;
    }

    public abstract function delete($object_ids, $secure = true);
}
