<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Entity\ATopEntity;
use Concerto\PanelBundle\Repository\AEntityRepository;
use Concerto\PanelBundle\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AExportableSectionService extends ASectionService
{
    protected $validator;

    public function __construct(
        AEntityRepository $repository,
        ValidatorInterface $validator,
        AuthorizationCheckerInterface $securityAuthorizationChecker,
        TokenStorageInterface $securityTokenStorage,
        AdministrationService $administrationService,
        LoggerInterface $logger)
    {
        parent::__construct($repository, $securityAuthorizationChecker, $securityTokenStorage, $administrationService, $logger);

        $this->validator = $validator;
    }

    public function getExportFileName($prefix, $instructions, $format)
    {
        $ext = null;
        switch ($format) {
            case "yml":
            case "json":
                $ext = "concerto." . $format;
                break;
            case "compressed":
                $ext = "concerto";
                break;
        }
        $name = "";
        if (count($instructions) == 1) {
            $name = "_" . $instructions[0]["name"];
        }
        return $prefix . $name . '.' . $ext;
    }

    protected function formatImportName($name, $arr)
    {
        if ($name != "") {
            $name = str_replace("{{id}}", $arr['id'], $name);
            $name = str_replace("{{name}}", $arr['name'], $name);
        } else {
            $name = $arr['name'];
        }
        return $name;
    }

    protected function getNextValidName($name, $action, $old_name)
    {
        while ($this->doesNameExist($name) && ($action != 1 || $name != $old_name)) {
            $index = strripos($name, "_");
            if ($index !== -1) {
                $prefix = substr($name, 0, $index);
                $suffix = substr($name, $index + 1);
                if (is_numeric($suffix)) {
                    $suffix += 1;
                    $name = $prefix . "_" . $suffix;
                    continue;
                }
            }
            $name = $name . "_1";
        }
        return $name;
    }

    protected function doesNameExist($name)
    {
        return $this->repository->findOneBy(array("name" => $name)) != null;
    }

    public function convertToExportable($arr, $instruction = null, $secure = true)
    {
        unset($arr["updatedOn"]);
        unset($arr["updatedBy"]);
        unset($arr["owner"]);
        unset($arr["directLockBy"]);
        unset($arr["lockedBy"]);
        return $arr;
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
            $object->setUpdatedBy($user);
            $this->repository->save($object);
            return true;
        }
        return false;
    }

}
