<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Entity\User;
use Concerto\PanelBundle\Repository\UserRepository;
use Concerto\PanelBundle\Repository\RoleRepository;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserService extends ASectionService
{

    private static $uio_eligible_classes = array(
        "DataTable",
        "Test",
        "TestWizard",
        "ViewTemplate"
    );
    private $encoderFactory;
    private $roleRepository;
    private $uio;
    private $importService;
    private $validator;

    public function __construct(UserRepository $repository, RoleRepository $roleRepository, ValidatorInterface $validator, EncoderFactoryInterface $encoderFactory, AuthorizationCheckerInterface $securityAuthorizationChecker, $uio, ImportService $importService)
    {
        parent::__construct($repository, $securityAuthorizationChecker);

        $this->roleRepository = $roleRepository;
        $this->validator = $validator;
        $this->encoderFactory = $encoderFactory;
        $this->uio = $uio;
        $this->importService = $importService;
    }

    public function get($object_id, $createNew = false, $secure = true)
    {
        $object = parent::get($object_id, $createNew, $secure);
        if ($createNew && $object === null) {
            $object = new User();
        }
        return $object;
    }

    public function save(User $user, $object_id, $accessibility, $archived, $owner, $groups, $email, $username, $password, $passwordConfirmation, $role_super_admin, $role_test, $role_template, $role_table, $role_file, $role_wizard)
    {
        $errors = array();
        $object = $this->get($object_id);
        $validatation_groups = array("User");
        array_push($validatation_groups, "create");
        $new = false;
        if ($object === null) {
            $object = new User();
            $new = true;
        }
        $object->setUpdated();
        if ($user !== null)
            $object->setUpdatedBy($user->getUsername());
        $object->setEmail($email);
        $object->setUsername($username);

        if (!self::$securityOn || $this->securityAuthorizationChecker->isGranted(User::ROLE_SUPER_ADMIN)) {
            $object->setAccessibility($accessibility);
            $object->setGroups($groups);

            $role = $this->roleRepository->findOneByRole(User::ROLE_SUPER_ADMIN);
            if ($role_super_admin == "1") {
                if (!$object->hasRole($role)) {
                    $object->addRole($role);
                }
            } else {
                if ($object->hasRole($role)) {
                    $object->removeRole($role);
                }
            }

            $role = $this->roleRepository->findOneByRole(User::ROLE_TEST);
            if ($role_test == "1") {
                if (!$object->hasRole($role)) {
                    $object->addRole($role);
                }
            } else {
                if ($object->hasRole($role)) {
                    $object->removeRole($role);
                }
            }

            $role = $this->roleRepository->findOneByRole(User::ROLE_TEMPLATE);
            if ($role_template == "1") {
                if (!$object->hasRole($role)) {
                    $object->addRole($role);
                }
            } else {
                if ($object->hasRole($role)) {
                    $object->removeRole($role);
                }
            }

            $role = $this->roleRepository->findOneByRole(User::ROLE_TABLE);
            if ($role_table == "1") {
                if (!$object->hasRole($role)) {
                    $object->addRole($role);
                }
            } else {
                if ($object->hasRole($role)) {
                    $object->removeRole($role);
                }
            }

            $role = $this->roleRepository->findOneByRole(User::ROLE_FILE);
            if ($role_file == "1") {
                if (!$object->hasRole($role)) {
                    $object->addRole($role);
                }
            } else {
                if ($object->hasRole($role)) {
                    $object->removeRole($role);
                }
            }

            $role = $this->roleRepository->findOneByRole(User::ROLE_WIZARD);
            if ($role_wizard == "1") {
                if (!$object->hasRole($role)) {
                    $object->addRole($role);
                }
            } else {
                if ($object->hasRole($role)) {
                    $object->removeRole($role);
                }
            }
        }

        $object->setArchived($archived);
        $encoder = $this->encoderFactory->getEncoder($object);
        if ($password != null) {
            $password = $encoder->encodePassword($password, $object->getSalt());
            $object->setPassword($password);
            $passwordConfirmation = $encoder->encodePassword($passwordConfirmation, $object->getSalt());
            $object->setPasswordConfirmation($passwordConfirmation);
        } else {
            $object->setPasswordConfirmation($object->getPassword());
        }


        foreach ($this->validator->validate($object, null, $validatation_groups) as $err) {
            array_push($errors, $err->getMessage());
        }
        if (count($errors) > 0) {
            return array("object" => null, "errors" => $errors);
        }
        $this->repository->save($object);
        if ($new) {
            $result = $this->initializeUserObjects($object);
            foreach ($result as $r) {
                if (array_key_exists("errors", $r) && $r["errors"]) {
                    return $r;
                }
            }
        }
        return array("object" => $object, "errors" => $errors);
    }

    private function initializeUserObjects(User $user)
    {
        $result = array();
        foreach ($this->uio as $group => $classes) {
            if (!$user->hasGroup($group))
                continue;
            foreach (self::$uio_eligible_classes as $class) {
                if (array_key_exists($class, $classes)) {
                    foreach ($classes[$class] as $obj) {
                        $result = array_merge($result, $this->importService->copy($class, $user, $obj["id"], $obj["name"]));
                    }
                }
            }
        }
        return $result;
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

}
