<?php

namespace Concerto\PanelBundle\Service;

use Symfony\Component\Validator\Validator\RecursiveValidator;
use Concerto\PanelBundle\Entity\TestWizard;
use Concerto\PanelBundle\Service\TestWizardParamService;
use Concerto\PanelBundle\Repository\TestWizardRepository;
use Concerto\PanelBundle\Entity\User;
use Concerto\PanelBundle\Entity\AEntity;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class TestWizardService extends AExportableSectionService {

    private $testService;
    private $testWizardParamService;

    public function __construct(TestWizardRepository $repository, RecursiveValidator $validator, TestService $testService, TestWizardParamService $paramService, AuthorizationChecker $securityAuthorizationChecker) {
        parent::__construct($repository, $validator, $securityAuthorizationChecker);

        $this->testService = $testService;
        $this->testWizardParamService = $paramService;
    }

    public function get($object_id, $createNew = false) {
        $object = null;
        if ($object_id !== null) {
            $object = parent::get($object_id, $createNew);
        }
        if ($createNew && $object === null) {
            $object = new TestWizard();
        }
        return $object;
    }

    public function save(User $user, $object_id, $name, $description, $accessibility, $protected, $archived, $owner, $groups, $test, $serializedSteps) {
        $errors = array();
        $object = $this->get($object_id);
        $new = false;
        if ($object === null) {
            $object = new TestWizard();
            $new = true;
            $object->setOwner($user);
        }
        $object->setUpdated();
        $object->setUpdatedBy($user);
        if (count($errors) > 0) {
            return array("object" => null, "errors" => $errors);
        }
        $object->setName($name);
        if ($description !== null) {
            $object->setDescription($description);
        }
        if (!$new && $object->isProtected() == $protected && $protected) {
            array_push($errors, "validate.protected.mod");
        }

        if ($this->securityAuthorizationChecker->isGranted(User::ROLE_SUPER_ADMIN)) {
            $object->setAccessibility($accessibility);
            $object->setOwner($owner);
            $object->setGroups($groups);
        }

        $object->setProtected($protected);
        $object->setArchived($archived);
        if ($test !== null) {
            $object->setTest($test);
        }
        foreach ($this->validator->validate($object) as $err) {
            array_push($errors, $err->getMessage());
        }
        if (count($errors) > 0) {
            return array("object" => null, "errors" => $errors);
        }
        $this->repository->save($object);
        $this->updateParamValues($serializedSteps);
        $this->repository->refresh($object);
        $object = $this->get($object->getId());
        return array("object" => $object, "errors" => $errors);
    }

    public function updateParamValues($serializedSteps) {
        if (!$serializedSteps)
            return;
        $steps = json_decode($serializedSteps, true);
        foreach ($steps as $step) {
            if (!array_key_exists("params", $step)) {
                continue;
            }
            foreach ($step["params"] as $param) {
                $this->testWizardParamService->update($param["id"], $param["value"], $param["order"]);
            }
        }
    }

    public function delete($object_ids) {
        $object_ids = explode(",", $object_ids);

        $result = array();
        foreach ($object_ids as $object_id) {
            $object = $this->get($object_id);
            if ($object === null)
                continue;
            if ($object->isProtected()) {
                array_push($result, array("object" => $object, "errors" => array("validate.protected.mod")));
                continue;
            }
            $this->repository->delete($object);
            array_push($result, array("object" => $object, "errors" => array()));
        }
        return $result;
    }

    public function entityToArray(AEntity $ent) {
        $e = $ent->jsonSerialize();
        return $e;
    }

    public function importFromArray(User $user, $newName, $obj, &$map, &$queue) {
        $formattedName = $this->formatImportName($user, $newName, $obj);

        $pre_queue = array();
        $test = null;
        if (array_key_exists("Test", $map) && array_key_exists("id" . $obj["test"], $map["Test"])) {
            $test_id = $map["Test"]["id" . $obj["test"]];
            $test = $this->testService->repository->find($test_id);
        }
        if (!$test) {
            array_push($pre_queue, $obj["testObject"]);
        }
        if (count($pre_queue) > 0) {
            return array("pre_queue" => $pre_queue);
        }

        $ent = new TestWizard();
        $ent->setName($formattedName);
        $ent->setTest($test);
        $ent->setDescription($obj["description"]);
        $ent->setGlobalId($obj["globalId"]);
        $ent->setOwner($user);
        $ent->setProtected($obj["protected"] == "1");
        $ent_errors = $this->validator->validate($ent);
        $ent_errors_msg = array();
        foreach ($ent_errors as $err) {
            array_push($ent_errors_msg, $err->getMessage());
        }
        if (count($ent_errors_msg) > 0) {
            return array("errors" => $ent_errors_msg, "entity" => null, "source" => $obj);
        }
        $this->repository->save($ent);

        if (!array_key_exists("TestWizard", $map)) {
            $map["TestWizard"] = array();
        }
        $map["TestWizard"]["id" . $obj["id"]] = $ent->getId();

        $queue = array_merge($queue, $obj["steps"]);

        return array("errors" => null, "entity" => $ent);
    }

}
