<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Entity\AEntity;
use Concerto\PanelBundle\Entity\ATopEntity;
use Concerto\PanelBundle\Entity\User;
use Concerto\PanelBundle\Repository\AEntityRepository;
use Concerto\PanelBundle\Security\ObjectVoter;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

abstract class ASectionService
{
    public static $securityOn = true;
    public $repository;
    protected $securityAuthorizationChecker;
    protected $securityTokenStorage;

    public function __construct(AEntityRepository $repository, AuthorizationCheckerInterface $securityAuthorizationChecker, TokenStorageInterface $securityTokenStorage)
    {
        $this->repository = $repository;
        $this->securityAuthorizationChecker = $securityAuthorizationChecker;
        $this->securityTokenStorage = $securityTokenStorage;
    }

    public static function getObjectImportInstruction($obj, $instructions)
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

    public function canBeModified($object_ids, $timestamp = null, &$errorMessages = null)
    {
        if ($timestamp === null) $timestamp = time();

        //accessed from command line
        if($this->securityTokenStorage->getToken() === null) return true;

        /** @var User $user */
        $user = $this->securityTokenStorage->getToken()->getUser();
        $object_ids = explode(",", $object_ids);
        foreach ($object_ids as $object_id) {
            /** @var AEntity|null $object */
            $object = $this->get($object_id);
            if ($object) {
                if ($object->getLockBy() && $object->getLockBy() != $user) {
                    $errorMessages = ["validate.locked"];
                    return false;
                }
                if ($object->getDeepUpdated()->getTimestamp() > $timestamp && $object->getDeepUpdatedBy() != $user->getUsername()) {
                    $errorMessages = ["validate.outdated"];
                    return false;
                }
            }
        }
        return true;
    }

    public function toggleLock($object_id)
    {
        /** @var User $user */
        $user = $this->securityTokenStorage->getToken()->getUser();
        /** @var ATopEntity $object */
        $object = $this->get($object_id);
        if ($object) {
            $isLocked = $object->getDirectLockBy() !== null;
            $object->setDirectLockBy($isLocked ? null : $user);
            $object->setUpdated();
            $object->setUpdatedBy($user);
            $this->repository->save($object);
            return true;
        }
        return false;
    }

    public abstract function delete($object_ids, $secure = true);
}
