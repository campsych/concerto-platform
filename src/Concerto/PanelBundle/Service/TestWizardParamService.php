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
use Concerto\PanelBundle\Service\TestNodePortService;

class TestWizardParamService extends ASectionService {

    public static $simpleTypes = [0, 1, 2, 3, 4, 5, 6, 8, 11];
    private $validator;
    private $testVariableService;
    private $testWizardRepository;
    private $testWizardStepRepository;
    private $testNodePortService;

    public function __construct(TestWizardParamRepository $repository, RecursiveValidator $validator, TestVariableService $testVariableService, TestWizardRepository $testWizardRepository, TestWizardStepRepository $testWizardStepRepository, AuthorizationChecker $securityAuthorizationChecker, TestNodePortService $testNodePortService) {
        parent::__construct($repository, $securityAuthorizationChecker);

        $this->validator = $validator;
        $this->testVariableService = $testVariableService;
        $this->testWizardRepository = $testWizardRepository;
        $this->testWizardStepRepository = $testWizardStepRepository;
        $this->testNodePortService = $testNodePortService;
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

    public function importFromArray(User $user, $instructions, $obj, &$map, &$queue) {
        $pre_queue = array();
        if (!array_key_exists("TestWizardParam", $map))
            $map["TestWizardParam"] = array();
        if (array_key_exists("id" . $obj["id"], $map["TestWizardParam"])) {
            return array("errors" => null, "entity" => $map["TestWizardParam"]["id" . $obj["id"]]);
        }

        $variable = null;
        if (array_key_exists("TestVariable", $map) && array_key_exists("id" . $obj["testVariable"], $map["TestVariable"])) {
            $variable = $map["TestVariable"]["id" . $obj["testVariable"]];
        }

        $wizard = null;
        if (array_key_exists("TestWizard", $map) && array_key_exists("id" . $obj["wizard"], $map["TestWizard"])) {
            $wizard = $map["TestWizard"]["id" . $obj["wizard"]];
        }

        $step = null;
        if (array_key_exists("TestWizardStep", $map) && array_key_exists("id" . $obj["wizardStep"], $map["TestWizardStep"])) {
            $step = $map["TestWizardStep"]["id" . $obj["wizardStep"]];
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
        if ($parent_instruction["action"] == 1 && $src_ent) {
            $result = $this->importConvert($user, null, $src_ent, $obj, $map, $queue, $step, $variable, $wizard);
        } else if ($parent_instruction["action"] == 2 && $src_ent) {
            $map["TestWizardParam"]["id" . $obj["id"]] = $src_ent;
            $result = array("errors" => null, "entity" => $src_ent);
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

        $map["TestWizardParam"]["id" . $obj["id"]] = $ent;
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
        $map["TestWizardParam"]["id" . $obj["id"]] = $ent;

        $this->onObjectSaved($user, $ent, $old_ent);
        $this->onConverted($ent, $old_ent);

        return array("errors" => null, "entity" => $ent);
    }

    private function onConverted($new_ent, $old_ent) {
        //TODO 
    }

    private function onObjectSaved(User $user, TestWizardParam $newParam, $oldParam) {
        $this->updateValues($user, $newParam, $oldParam);
    }

    private function updateValues(User $user, TestWizardParam $newParam, $oldParam) {
        $newDef = $newParam->getDefinition();
        $oldDef = null;
        $newVal = $newParam->getValue();
        $oldVal = null;
        $newType = $newParam->getType();
        $oldType = null;
        if (!in_array($newParam->getType(), self::$simpleTypes)) {
            $newVal = json_decode($newVal, true);
        }
        if ($oldParam != null) {
            $oldDef = $oldParam->getDefinition();
            $oldVal = $oldParam->getValue();
            $oldType = $oldParam->getType();
            if (!in_array($oldParam->getType(), self::$simpleTypes)) {
                $oldVal = json_decode($oldVal, true);
            }
        }

        //param update
        $this->getChildrenMergedValue($user, $newType, $oldType, $newDef, $oldDef, $newVal, $oldVal, $newVal, true, true);
        $val = $newVal;
        if (!in_array($newParam->getType(), self::$simpleTypes)) {
            $val = json_encode($val);
        }
        $newParam->setValue($val);
        $this->repository->save($newParam);

        //resulting tests variables update
        foreach ($newParam->getWizard()->getResultingTests() as $test) {
            foreach ($test->getVariables() as $var) {
                $pvar = $var->getParentVariable();
                if ($newParam->getVariable()->getId() == $pvar->getId()) {
                    $dstVal = $var->getValue();
                    if (!in_array($oldType, self::$simpleTypes)) {
                        $dstVal = json_decode($dstVal, true);
                    }
                    $this->getChildrenMergedValue($user, $newParam->getType(), $oldType, $newDef, $oldDef, $newVal, $oldVal, $dstVal);
                    $val = $dstVal;
                    if (!in_array($newType, self::$simpleTypes)) {
                        $val = json_encode($val);
                    }
                    $var->setValue($val);
                    $this->testVariableService->update($user, $var);

                    ///

                    $nodes = $var->getTest()->getSourceForNodes();
                    foreach ($nodes as $node) {
                        $ports = $node->getPorts();
                        foreach ($ports as $port) {
                            if ($port->getVariable()->getId() == $var->getId()) {
                                $portDstVal = $port->getValue();
                                if (!in_array($oldType, self::$simpleTypes)) {
                                    $portDstVal = json_decode($portDstVal, true);
                                }
                                $this->getChildrenMergedValue($user, $newParam->getType(), $oldType, $newDef, $oldDef, $newVal, $oldVal, $portDstVal);
                                $portVal = $portDstVal;
                                if (!in_array($newType, self::$simpleTypes)) {
                                    $portVal = json_encode($portVal);
                                }
                                $port->setValue($portVal);
                                $this->testNodePortService->update($port);
                                break;
                            }
                        }
                    }
                }
            }
        }
    }

    private function getChildrenMergedValue(User $user, $newType, $oldType, $newDef, $oldDef, &$newVal, $oldVal, &$dstVal, $default = true, $dstIsParam = false) {
        //type change
        $newField = $oldType === null;
        $typeChanged = $newType != $oldType;

        $typesCompatible = true;
        $newTypeSimple = in_array($newType, self::$simpleTypes);
        if ($newField || ($typeChanged && in_array($newType, self::$simpleTypes) != in_array($oldType, self::$simpleTypes))) {
            $typesCompatible = false;
        }

        if ($newTypeSimple) {
            //new type simple
            if ($typeChanged && !$typesCompatible) {
                switch ((int) $newType) {
                    case 4:
                        $dstVal = "0";
                        break;
                    default:
                        $dstVal = "";
                        break;
                }
            }

            //check if should use default value when simple
            $default &= $typeChanged || $oldVal === null || $oldDef === null || ($newTypeSimple && array_key_exists("defvalue", $oldDef) && $oldDef["defvalue"] == $oldVal);
            if ($default && is_array($newDef) && array_key_exists("defvalue", $newDef)) {
                $dstVal = $newDef["defvalue"];
            }
        } else {
            //new type complex
            $default &= $typeChanged || ($oldVal == $dstVal);
            if ($typeChanged)
                $dstVal = array();
            if ($default && !$dstIsParam) {
                $dstVal = $newVal;
                return;
            }

            //complex type recursion
            switch ((int) $newType) {
                //group type
                case 9:
                    foreach ($newDef["fields"] as $field) {
                        if (!is_array($dstVal) || !array_key_exists($field["name"], $dstVal)) {
                            $dstVal[$field["name"]] = null;
                        }
                        $dstFieldVal = &$dstVal[$field["name"]];
                        $newFieldVal = &$newVal[$field["name"]];
                        $newFieldDef = null;
                        if (array_key_exists("definition", $field))
                            $newFieldDef = $field["definition"];
                        $oldFieldType = null;
                        $oldFieldDef = null;
                        $oldFieldVal = null;
                        if (!$typeChanged) {
                            foreach ($oldDef["fields"] as $oldField) {
                                if ($oldField["name"] == $field["name"]) {
                                    $oldFieldType = $oldField["type"];
                                    if (is_array($oldField) && array_key_exists("definition", $oldField))
                                        $oldFieldDef = $oldField["definition"];
                                    if (is_array($oldVal) && array_key_exists($oldField["name"], $oldVal))
                                        $oldFieldVal = $oldVal[$oldField["name"]];
                                    break;
                                }
                            }
                        }
                        $this->getChildrenMergedValue($user, $field["type"], $oldFieldType, $newFieldDef, $oldFieldDef, $newFieldVal, $oldFieldVal, $dstFieldVal, $default);
                    }
                    break;
                //list type
                case 10:
                    for ($i = 0; $i < count($dstVal); $i++) {
                        $oldElemType = null;
                        $oldElemDef = null;
                        $oldElemVal = null;
                        if (!$typeChanged) {
                            $oldElemType = $oldDef["element"]["type"];
                            if (array_key_exists("definition", $oldDef["element"]))
                                $oldElemDef = $oldDef["element"]["definition"];
                            if (count($oldVal) > $i)
                                $oldElemVal = $oldVal[$i];
                            $newElemVal = null;
                            if (count($newVal) > $i)
                                $newElemVal = $newVal[$i];
                        }
                        $this->getChildrenMergedValue($user, $newDef["element"]["type"], $oldElemType, $newDef["element"]["definition"], $oldElemDef, $newElemVal, $oldElemVal, $dstVal[$i], $default);
                    }
                    break;
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
