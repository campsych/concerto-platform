<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Entity\AEntity;
use Concerto\PanelBundle\Entity\ATopEntity;
use Concerto\PanelBundle\Entity\User;
use Concerto\PanelBundle\Repository\AEntityRepository;
use Concerto\PanelBundle\Security\ObjectVoter;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

abstract class ASectionService
{
    public static $securityOn = true;
    public $repository;
    protected $securityAuthorizationChecker;
    protected $securityTokenStorage;
    protected $administrationService;
    protected $logger;

    public function __construct(AEntityRepository $repository, AuthorizationCheckerInterface $securityAuthorizationChecker, TokenStorageInterface $securityTokenStorage, AdministrationService $administrationService, LoggerInterface $logger)
    {
        $this->repository = $repository;
        $this->securityAuthorizationChecker = $securityAuthorizationChecker;
        $this->securityTokenStorage = $securityTokenStorage;
        $this->administrationService = $administrationService;
        $this->logger = $logger;
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
        if ($this->securityTokenStorage->getToken() === null) return true;

        if ($this->administrationService->isContentBlocked()) {
            $errorMessages = ["validate.blocked"];
            return false;
        }

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
                if ($object->getTopEntity()->getUpdated()->getTimestamp() > $timestamp) {
                    $errorMessages = ["validate.outdated"];
                    return false;
                }
            }
        }
        return true;
    }

    public abstract function delete($object_ids, $secure = true);
}
