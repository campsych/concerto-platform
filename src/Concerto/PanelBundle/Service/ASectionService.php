<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Repository\AEntityRepository;
use Concerto\PanelBundle\Security\ObjectVoter;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

abstract class ASectionService
{

    public static $securityOn = true;
    public $repository;
    protected $securityAuthorizationChecker;

    public function __construct(AEntityRepository $repository, AuthorizationCheckerInterface $securityAuthorizationChecker)
    {
        $this->repository = $repository;
        $this->securityAuthorizationChecker = $securityAuthorizationChecker;
    }

    protected static function getObjectImportInstruction($obj, $instructions)
    {
        foreach ($instructions as $instruction) {
            if ($instruction["class_name"] == $obj["class_name"] && $instruction["id"] == $obj["id"])
                return $instruction;
        }
        return null;
    }

    public function getAll()
    {
        return $this->authorizeCollection($this->repository->findAll());
    }

    public function authorizeObject($object)
    {
        if (!self::$securityOn)
            return $object;
        if ($this->securityAuthorizationChecker->isGranted(ObjectVoter::ATTR_ACCESS, $object))
            return $object;
        return null;
    }

    public function authorizeCollection($collection)
    {
        if (!self::$securityOn)
            return $collection;
        $result = array();
        foreach ($collection as $object) {
            if ($this->authorizeObject($object))
                array_push($result, $object);
        }
        return $result;
    }

    public function get($object_id, $createNew = false, $secure = true)
    {
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
