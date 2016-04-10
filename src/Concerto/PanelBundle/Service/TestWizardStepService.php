<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Repository\TestWizardStepRepository;
use Concerto\PanelBundle\Entity\TestWizardStep;
use Symfony\Component\Validator\Validator\RecursiveValidator;
use Concerto\PanelBundle\Entity\User;
use Concerto\PanelBundle\Repository\TestWizardRepository;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class TestWizardStepService extends ASectionService {

    private $validator;
    private $testWizardRepository;

    public function __construct(TestWizardStepRepository $repository, RecursiveValidator $validator, TestWizardRepository $testWizardRepository, AuthorizationChecker $securityAuthorizationChecker) {
        parent::__construct($repository, $securityAuthorizationChecker);

        $this->validator = $validator;
        $this->testWizardRepository = $testWizardRepository;
    }

    public function get($object_id, $createNew = false, $secure = true) {
        $object = parent::get($object_id, $createNew, $secure);
        if ($createNew && $object === null) {
            $object = new TestWizardStep();
        }
        return $object;
    }

    public function getByTestWizard($wizard_id) {
        return $this->authorizeCollection($this->repository->findByTestWizard($wizard_id));
    }

    public function save(User $user, $object_id, $title, $description, $order, $wizard) {
        $errors = array();
        $object = $this->get($object_id);
        if ($object === null) {
            $object = new TestWizardStep();
        }
        $object->setUpdated();
        $object->setTitle($title);
        if ($description !== null) {
            $object->setDescription($description);
        }
        $object->setOrderNum($order);
        $object->setWizard($wizard);
        foreach ($this->validator->validate($object) as $err) {
            array_push($errors, $err->getMessage());
        }
        if (count($errors) > 0) {
            return array("object" => null, "errors" => $errors);
        }
        $this->repository->save($object);
        return array("object" => $object, "errors" => $errors);
    }

    public function delete($object_ids, $secure = true) {
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

    public function clear($wizard_id) {
        $wizard = $this->authorizeObject($this->testWizardRepository->find($wizard_id));
        if ($wizard)
            $this->repository->deleteByTestWizard($wizard_id);
        return array("errors" => array());
    }

    public function entityToArray(TestWizardStep $ent) {
        $e = $ent->jsonSerialize();
        return $e;
    }

    public function importFromArray(User $user, $newName, $obj, &$map, &$queue) {
        $wizard = null;
        if (array_key_exists("TestWizard", $map)) {
            $wizard_id = $map["TestWizard"]["id" . $obj["wizard"]];
            $wizard = $this->testWizardRepository->find($wizard_id);
        }

        $ent = new TestWizardStep();
        $ent->setColsNum($obj["colsNum"]);
        $ent->setDescription($obj["description"]);
        $ent->setOrderNum($obj["orderNum"]);
        $ent->setTitle($obj["title"]);
        $ent->setWizard($wizard);
        $ent->setGlobalId($obj["globalId"]);
        $ent_errors = $this->validator->validate($ent);
        $ent_errors_msg = array();
        foreach ($ent_errors as $err) {
            array_push($ent_errors_msg, $err->getMessage());
        }
        if (count($ent_errors_msg) > 0) {
            return array("errors" => $ent_errors_msg, "entity" => null, "source" => $obj);
        }
        $this->repository->save($ent);

        if (!array_key_exists("TestWizardStep", $map)) {
            $map["TestWizardStep"] = array();
        }
        $map["TestWizardStep"]["id" . $obj["id"]] = $ent->getId();

        $queue = array_merge($queue, $obj["params"]);

        return array("errors" => null, "entity" => $ent);
    }

}
