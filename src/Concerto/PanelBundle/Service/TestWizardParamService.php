<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Repository\TestWizardParamRepository;
use Concerto\PanelBundle\Entity\TestWizardParam;
use Symfony\Component\Validator\Validator\RecursiveValidator;
use Concerto\PanelBundle\Entity\User;
use Concerto\PanelBundle\Service\TestVariableService;
use Concerto\PanelBundle\Repository\TestWizardRepository;
use Concerto\PanelBundle\Repository\TestWizardStepRepository;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Concerto\PanelBundle\Security\ObjectVoter;

class TestWizardParamService extends ASectionService {

    private $validator;
    private $testVariableService;
    private $testWizardRepository;
    private $testWizardStepRepository;

    public function __construct(TestWizardParamRepository $repository, RecursiveValidator $validator, TestVariableService $testVariableService, TestWizardRepository $testWizardRepository, TestWizardStepRepository $testWizardStepRepository, AuthorizationChecker $securityAuthorizationChecker) {
        parent::__construct($repository, $securityAuthorizationChecker);

        $this->validator = $validator;
        $this->testVariableService = $testVariableService;
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
        $old_obj = null;
        if ($object === null) {
            $object = new TestWizardParam();
        } else {
            $old_obj = clone $object;
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
        $this->onObjectSaved($user, $object, $old_obj);

        return array("object" => $object, "errors" => $errors);
    }

    public function update(User $user, $object, $oldObj) {
        $this->repository->save($object);
        $this->onObjectSaved($user, $object, $oldObj);
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
        $wizard = parent::authorizeObject($this->testWizardRepository->find($wizard_id));
        if ($wizard)
            $this->repository->deleteByTestWizard($wizard_id);
        return array("errors" => array());
    }

    public function entityToArray(TestWizardParam $ent) {
        $e = $ent->jsonSerialize();
        return $e;
    }

    public function importFromArray(User $user, $instructions, $obj, &$map, &$queue) {
        $pre_queue = array();
        if (!array_key_exists("TestWizardParam", $map))
            $map["TestWizardParam"] = array();
        if (array_key_exists("id" . $obj["id"], $map["TestWizardParam"])) {
            return array("errors" => null, "entity" => $this->get($map["TestWizardParam"]["id" . $obj["id"]]));
        }

        $variable = null;
        if (array_key_exists("TestVariable", $map)) {
            $variable_id = $map["TestVariable"]["id" . $obj["testVariable"]];
            $variable = $this->testVariableService->get($variable_id);
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

        $parent_instruction = self::getObjectImportInstruction(array(
                    "class_name" => "TestWizard",
                    "id" => $obj["wizard"]
                        ), $instructions);
        $result = array();
        $src_ent = $this->findConversionSource($obj, $map);
        if ($parent_instruction["action"] == 1 && $src_ent)
            $result = $this->importConvert($user, null, $src_ent, $obj, $map, $queue, $step, $variable, $wizard);
        else if ($parent_instruction["action"] == 2) {
            $map["TestWizardParam"]["id" . $obj["id"]] = $obj["id"];
            $result = array("errors" => null, "entity" => $this->get($obj["id"]));
        } else
            $result = $this->importNew($user, null, $obj, $map, $queue, $step, $variable, $wizard);

        return $result;
    }

    protected function importNew(User $user, $new_name, $obj, &$map, &$queue, $step, $variable, $wizard) {
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
        $this->onObjectSaved($user, $ent, null);

        $map["TestWizardParam"]["id" . $obj["id"]] = $ent->getId();
        return array("errors" => null, "entity" => $ent);
    }

    protected function findConversionSource($obj, $map) {
        $wizard = $map["TestWizard"]["id" . $obj["wizard"]];
        $variable = $map["TestVariable"]["id" . $obj["testVariable"]];

        $ent = $this->repository->findOneBy(array(
            "wizard" => $wizard,
            "variable" => $variable
        ));
        if ($ent == null)
            return null;
        return $this->get($ent->getId());
    }

    protected function importConvert(User $user, $new_name, $src_ent, $obj, &$map, &$queue, $step, $variable, $wizard) {
        $old_ent = clone $src_ent;
        $ent = $src_ent;
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
        $map["TestWizardParam"]["id" . $obj["id"]] = $ent->getId();

        $this->onObjectSaved($user, $ent, $old_ent);
        $this->onConverted($ent, $old_ent);

        return array("errors" => null, "entity" => $ent);
    }

    private function onConverted($new_ent, $old_ent) {
        //TODO 
    }

    private function onObjectSaved(User $user, TestWizardParam $newObj, $oldObj) {
        if ($oldObj === null)
            return;
        $tests = $newObj->getWizard()->getResultingTests();
        foreach ($tests as $test) {
            $vars = $test->getVariables();
            foreach ($vars as $var) {
                $pvar = $var->getParentVariable();
                if ($newObj->getVariable()->getId() == $pvar->getId()) {
                    if ($oldObj->getValue() == $var->getValue()) {
                        $var->setValue($newObj->getValue());
                        $this->testVariableService->update($user, $var);
                    }
                    continue;
                }
            }
        }
    }

    public function authorizeObject($object) {
        if (!self::$securityOn)
            return $object;
        if ($object && $this->securityAuthorizationChecker->isGranted(ObjectVoter::ATTR_ACCESS, $object->getWizard()))
            return $object;
        return null;
    }

}
