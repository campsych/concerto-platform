<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Repository\TestWizardParamRepository;
use Concerto\PanelBundle\Entity\TestWizardParam;
use Symfony\Component\Validator\Validator\RecursiveValidator;
use Concerto\PanelBundle\Entity\User;
use Concerto\PanelBundle\Repository\TestVariableRepository;
use Concerto\PanelBundle\Repository\TestWizardRepository;
use Concerto\PanelBundle\Repository\TestWizardStepRepository;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class TestWizardParamService extends ASectionService {

    private $validator;
    private $testVariableRepository;
    private $testWizardRepository;
    private $testWizardStepRepository;

    public function __construct(TestWizardParamRepository $repository, RecursiveValidator $validator, TestVariableRepository $testVariableRepository, TestWizardRepository $testWizardRepository, TestWizardStepRepository $testWizardStepRepository, AuthorizationChecker $securityAuthorizationChecker) {
        parent::__construct($repository, $securityAuthorizationChecker);

        $this->validator = $validator;
        $this->testVariableRepository = $testVariableRepository;
        $this->testWizardRepository = $testWizardRepository;
        $this->testWizardStepRepository = $testWizardStepRepository;
    }

    public function get($object_id, $createNew = false, $secure = true) {
        $object = parent::get($object_id, $createNew, $secure);
        if ($createNew && $object === null) {
            $object = new TestWizardParam();
        }
        return $object;
    }

    public function getByTestWizard($wizard_id) {
        return $this->authorizeCollection($this->repository->findByTestWizard($wizard_id));
    }

    public function getByTestWizardAndType($wizard_id, $type) {
        return $this->authorizeCollection($this->repository->findByTestWizardAndType($wizard_id, $type));
    }

    public function save(User $user, $object_id, $variable, $label, $type, $serializedDefinition, $hideCondition, $description, $passableThroughUrl, $value, $wizardStep, $order, $wizard) {
        $errors = array();
        $object = $this->get($object_id);
        if ($object === null) {
            $object = new TestWizardParam();
        }
        $object->setUpdated();
        if ($variable != null) {
            $object->setVariable($variable);
        }
        $object->setLabel($label);
        $object->setType($type);

        if ($description !== null) {
            $object->setDescription($description);
        }
        $object->setPassableThroughUrl($passableThroughUrl);
        $object->setValue($value);
        if ($wizardStep != null) {
            $object->setStep($wizardStep);
        }
        $object->setOrder($order);
        $object->setWizard($wizard);
        $object->setDefinition(json_decode($serializedDefinition, true));
        $object->setHideCondition($hideCondition);
        foreach ($this->validator->validate($object) as $err) {
            array_push($errors, $err->getMessage());
        }
        if (count($errors) > 0) {
            return array("object" => null, "errors" => $errors);
        }
        $this->repository->save($object);
        $this->repository->refresh($object);
        $object = $this->get($object->getId());

        return array("object" => $object, "errors" => $errors);
    }

    public function update($id, $value, $order) {
        $object = $this->get($id);
        if ($object) {
            $object->setValue($value);
            $object->setOrder($order);
            $this->repository->save($object);
        }
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

    public function entityToArray(TestWizardParam $ent) {
        $e = $ent->jsonSerialize();
        return $e;
    }

    public function importFromArray(User $user, $newName, $obj, &$map, &$queue) {
        $pre_queue = array();
        if (array_key_exists("TestWizardParam", $map) && array_key_exists("id" . $obj["id"], $map["TestWizardParam"])) {
            return(array());
        }
        
        $variable = null;
        if (array_key_exists("TestVariable", $map)) {
            $variable_id = $map["TestVariable"]["id" . $obj["testVariable"]];
            $variable = $this->testVariableRepository->find($variable_id);
        }

        $wizard = null;
        if (array_key_exists("TestWizard", $map)) {
            $wizard_id = $map["TestWizard"]["id" . $obj["wizard"]];
            $wizard = $this->testWizardRepository->find($wizard_id);
        }

        $step = null;
        if (array_key_exists("TestWizardStep", $map)) {
            $step_id = $map["TestWizardStep"]["id" . $obj["wizardStep"]];
            $step = $this->testWizardStepRepository->find($step_id);
        }
        
        if (count($pre_queue) > 0) {
            return array("pre_queue" => $pre_queue);
        }

        $ent = new TestWizardParam();
        $ent->setDescription($obj["description"]);
        $ent->setLabel($obj["label"]);
        $ent->setPassableThroughUrl($obj["passableThroughUrl"]);
        $ent->setStep($step);
        $ent->setOrder($obj["order"]);
        $ent->setType($obj["type"]);
        $ent->setValue($obj["value"]);
        $ent->setVariable($variable);
        $ent->setWizard($wizard);
        $ent->setDefinition($obj["definition"]);
        $ent->setGlobalId($obj["globalId"]);
        $ent->setHideCondition($obj["hideCondition"]);
        $ent_errors = $this->validator->validate($ent);
        $ent_errors_msg = array();
        foreach ($ent_errors as $err) {
            array_push($ent_errors_msg, $err->getMessage());
        }
        if (count($ent_errors_msg) > 0) {
            return array("errors" => $ent_errors_msg, "entity" => null, "source" => $obj);
        }
        $this->repository->save($ent);

        if (!array_key_exists("TestWizardParam", $map)) {
            $map["TestWizardParam"] = array();
        }
        $map["TestWizardParam"]["id" . $obj["id"]] = $ent->getId();

        return array("errors" => null, "entity" => $ent);
    }

}
