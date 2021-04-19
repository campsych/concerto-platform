<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Entity\DataTable;
use Concerto\PanelBundle\Entity\Test;
use Concerto\PanelBundle\Entity\TestNodePort;
use Concerto\PanelBundle\Entity\TestVariable;
use Concerto\PanelBundle\Entity\ViewTemplate;
use Concerto\PanelBundle\Repository\TestNodeRepository;
use Concerto\PanelBundle\Repository\TestWizardParamRepository;
use Concerto\PanelBundle\Entity\TestWizardParam;
use Psr\Log\LoggerInterface;
use Concerto\PanelBundle\Entity\User;
use Concerto\PanelBundle\Repository\TestWizardRepository;
use Concerto\PanelBundle\Repository\TestWizardStepRepository;
use Concerto\PanelBundle\Security\ObjectVoter;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TestWizardParamService extends ASectionService
{

    public static $simpleTypes = [0, 1, 2, 3, 4, 5, 6, 8, 11];
    private $validator;
    private $testVariableService;
    private $testWizardRepository;
    private $testWizardStepRepository;
    private $testNodePortService;
    private $testNodeRepository;

    public function __construct(
        TestWizardParamRepository $repository,
        ValidatorInterface $validator,
        TestVariableService $testVariableService,
        TestWizardRepository $testWizardRepository,
        TestWizardStepRepository $testWizardStepRepository,
        AuthorizationCheckerInterface $securityAuthorizationChecker,
        TestNodePortService $testNodePortService,
        LoggerInterface $logger,
        TokenStorageInterface $securityTokenStorage,
        TestNodeRepository $testNodeRepository,
        AdministrationService $administrationService
    )
    {
        parent::__construct($repository, $securityAuthorizationChecker, $securityTokenStorage, $administrationService, $logger);

        $this->validator = $validator;
        $this->testVariableService = $testVariableService;
        $this->testWizardRepository = $testWizardRepository;
        $this->testWizardStepRepository = $testWizardStepRepository;
        $this->testNodePortService = $testNodePortService;
        $this->testNodeRepository = $testNodeRepository;
    }

    public function get($object_id, $createNew = false, $secure = true)
    {
        $object = parent::get($object_id, $createNew, $secure);
        if ($createNew && $object === null) {
            $object = new TestWizardParam();
        }
        return $object;
    }

    public function getByTestWizard($wizard_id)
    {
        return $this->authorizeCollection($this->repository->findByWizard($wizard_id));
    }

    public function getByTestWizardAndType($wizard_id, $type)
    {
        return $this->authorizeCollection($this->repository->findByTestWizardAndType($wizard_id, $type));
    }

    public function save($object_id, $variable, $label, $type, $serializedDefinition, $hideCondition, $description, $passableThroughUrl, $value, $wizardStep, $order, $wizard)
    {
        $errors = array();
        $object = $this->get($object_id);
        $originalObject = null;
        if ($object === null) {
            $object = new TestWizardParam();
        } else {
            $originalObject = clone $object;
        }
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
        $this->update($object, $originalObject);

        return array("object" => $object, "errors" => $errors);
    }

    public function update(TestWizardParam $object, TestWizardParam $originalObject = null, $flush = true)
    {
        $isNew = $object->getId() === null;
        $changeSet = $this->repository->getChangeSet($object);
        if ($isNew || !empty($changeSet)) {
            $this->repository->save($object);
            $this->onObjectSaved($object, $originalObject, $flush);
        }
    }

    private function onObjectSaved(TestWizardParam $object, TestWizardParam $originalObject = null, $flush = true)
    {
        $this->updateValues($object, $originalObject, $flush);
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

    public function clear($wizard_id)
    {
        $wizard = parent::authorizeObject($this->testWizardRepository->find($wizard_id));
        if ($wizard)
            $this->repository->deleteByTestWizard($wizard_id);
        return array("errors" => array());
    }

    public function importFromArray($instructions, $obj, &$map, &$renames, &$queue)
    {
        $pre_queue = array();
        if (!isset($map["TestWizardParam"]))
            $map["TestWizardParam"] = array();
        if (isset($map["TestWizardParam"]["id" . $obj["id"]])) {
            return array("errors" => null, "entity" => $map["TestWizardParam"]["id" . $obj["id"]]);
        }

        $variable = null;
        if (isset($map["TestVariable"]) && isset($map["TestVariable"]["id" . $obj["testVariable"]])) {
            $variable = $map["TestVariable"]["id" . $obj["testVariable"]];
        }

        $wizard = null;
        if (isset($map["TestWizard"]) && isset($map["TestWizard"]["id" . $obj["wizard"]])) {
            $wizard = $map["TestWizard"]["id" . $obj["wizard"]];
        }

        $step = null;
        if (isset($map["TestWizardStep"]) && isset($map["TestWizardStep"]["id" . $obj["wizardStep"]])) {
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
            $result = $this->importConvert(null, $src_ent, $obj, $map, $renames, $queue, $step, $variable, $wizard);
        } else if ($parent_instruction["action"] == 2 && $src_ent) {
            $map["TestWizardParam"]["id" . $obj["id"]] = $src_ent;
            $result = array("errors" => null, "entity" => $src_ent);
        } else
            $result = $this->importNew(null, $obj, $map, $renames, $queue, $step, $variable, $wizard);

        return $result;
    }

    protected function importNew($new_name, $obj, &$map, $renames, &$queue, $step, $variable, $wizard)
    {
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

        $val = $ent->getValue();
        $def = $ent->getDefinition();
        foreach ($renames as $class => $renameMap) {
            foreach ($renameMap as $oldName => $newName) {
                $moded = self::modifyPropertiesOnRename($newName, $class, $oldName, $ent->getType(), $def, $val, false);
                if ($moded) {
                    $ent->setValue($val);
                    $ent->setDefinition($def);
                }
            }
        }

        $ent_errors = $this->validator->validate($ent);
        $ent_errors_msg = array();
        foreach ($ent_errors as $err) {
            array_push($ent_errors_msg, $err->getMessage());
        }
        if (count($ent_errors_msg) > 0) {
            return array("errors" => $ent_errors_msg, "entity" => null, "source" => $obj);
        }
        $this->update($ent, null);
        $map["TestWizardParam"]["id" . $obj["id"]] = $ent;
        return array("errors" => null, "entity" => $ent);
    }

    protected function findConversionSource($obj, $map)
    {
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

    protected function importConvert($new_name, $src_ent, $obj, &$map, $renames, &$queue, $step, $variable, $wizard)
    {
        $old_ent = clone $src_ent;
        $ent = $src_ent;
        $ent->setDescription($obj["description"]);
        $ent->setLabel($obj["label"]);
        $ent->setPassableThroughUrl($obj["passableThroughUrl"] == 1);
        $ent->setStep($step);
        $ent->setOrder($obj["order"]);
        $ent->setType($obj["type"]);
        $ent->setValue($obj["value"]);
        $ent->setVariable($variable);
        $ent->setWizard($wizard);
        $ent->setDefinition($obj["definition"]);
        $ent->setHideCondition($obj["hideCondition"]);

        $val = $ent->getValue();
        $def = $ent->getDefinition();
        foreach ($renames as $class => $renameMap) {
            foreach ($renameMap as $oldName => $newName) {
                $moded = self::modifyPropertiesOnRename($newName, $class, $oldName, $ent->getType(), $def, $val, false);
                if ($moded) {
                    $ent->setValue($val);
                    $ent->setDefinition($def);
                }
            }
        }

        $ent_errors = $this->validator->validate($ent);
        $ent_errors_msg = array();
        foreach ($ent_errors as $err) {
            array_push($ent_errors_msg, $err->getMessage());
        }
        if (count($ent_errors_msg) > 0) {
            return array("errors" => $ent_errors_msg, "entity" => null, "source" => $obj);
        }
        $this->update($ent, $old_ent);
        $map["TestWizardParam"]["id" . $obj["id"]] = $ent;
        return array("errors" => null, "entity" => $ent);
    }

    private function updateValues(TestWizardParam $newParam, $oldParam, $flush = true)
    {
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
        self::mergeValue($newType, $oldType, $newDef, $oldDef, $newVal, $oldVal, $newVal, true, true);
        $val = $newVal;
        if (!in_array($newParam->getType(), self::$simpleTypes)) {
            $val = json_encode($val);
        }
        $newParam->setValue($val);

        $user = null;
        $token = $this->securityTokenStorage->getToken();
        if ($token !== null) $user = $token->getUser();

        // do not use update here or it will do infinite loop
        $this->repository->save($newParam, $flush);

        //resulting tests variables update
        foreach ($newParam->getWizard()->getResultingTests() as $test) {
            foreach ($test->getVariables() as $var) {
                /** @var TestVariable $var */
                $pvar = $var->getParentVariable();
                if ($pvar !== null && $newParam->getVariable()->getId() == $pvar->getId()) {
                    $dstVal = $var->getValue();
                    if (!in_array($oldType, self::$simpleTypes)) {
                        $dstVal = json_decode($dstVal, true);
                    }
                    self::mergeValue($newParam->getType(), $oldType, $newDef, $oldDef, $newVal, $oldVal, $dstVal);
                    $val = $dstVal;
                    if (!in_array($newType, self::$simpleTypes)) {
                        $val = json_encode($val);
                    }
                    $update = false;
                    if ($newParam->isPassableThroughUrl() !== $var->isPassableThroughUrl()) {
                        $var->setPassableThroughUrl($newParam->isPassableThroughUrl());
                        $update = true;
                    }
                    if ($val !== $var->getValue()) {
                        $var->setValue($val);
                        $update = true;
                    }
                    if ($update) $this->testVariableService->update($var, $flush);

                    // ports update
                    $nodes = $var->getTest()->getSourceForNodes();
                    foreach ($nodes as $node) {
                        $ports = $node->getPorts();
                        /** @var TestNodePort $port */
                        foreach ($ports as $port) {
                            if ($port->getVariable() !== null && $port->getVariable()->getId() == $var->getId()) {
                                $portDstVal = $port->getValue();
                                if (!in_array($oldType, self::$simpleTypes)) {
                                    $portDstVal = json_decode($portDstVal, true);
                                }
                                self::mergeValue($newParam->getType(), $oldType, $newDef, $oldDef, $newVal, $oldVal, $portDstVal);
                                $portVal = $portDstVal;
                                if (!in_array($newType, self::$simpleTypes)) {
                                    $portVal = json_encode($portVal);
                                }
                                if ($portVal !== $port->getValue()) {
                                    $port->setValue($portVal);
                                    $this->testNodePortService->update($port, $flush);
                                }
                                break;
                            }
                        }
                    }
                }
            }
        }
    }

    public static function mergeValue($newType, $oldType, $newDef, $oldDef, &$newVal, $oldVal, &$mergedVal, $allowDefault = true, $isParam = false)
    {
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
                switch ((int)$newType) {
                    case 4:
                        $mergedVal = "0";
                        break;
                    default:
                        $mergedVal = "";
                        break;
                }
            }

            //check if should use default value when simple
            $allowDefault &= $typeChanged || $oldVal === null || $oldDef === null || ($isParam && isset($oldDef["defvalue"]) && $oldDef["defvalue"] == $oldVal);
            if ($allowDefault && is_array($newDef) && isset($newDef["defvalue"])) {
                $mergedVal = $newDef["defvalue"];
            }
        } else {
            //new type complex
            $allowDefault &= $typeChanged || ($oldVal == $mergedVal);
            if ($typeChanged)
                $mergedVal = array();
            if ($allowDefault && !$isParam) {
                $mergedVal = $newVal;
                return;
            }

            //complex type recursion
            switch ((int)$newType) {
                //group type
                case 9:
                    if ($oldDef !== null) {
                        foreach ($oldDef["fields"] as $oldField) {
                            $found = false;
                            foreach ($newDef["fields"] as $field) {
                                if ($oldField["name"] == $field["name"]) {
                                    $found = true;
                                    break;
                                }
                            }
                            if (!$found) {
                                unset($mergedVal[$oldField["name"]]);
                            }
                        }
                    }

                    foreach ($newDef["fields"] as $field) {
                        if (!is_array($mergedVal) || !isset($mergedVal[$field["name"]])) {
                            $mergedVal[$field["name"]] = null;
                        }
                        $dstFieldVal = &$mergedVal[$field["name"]];
                        $newFieldVal = &$newVal[$field["name"]];
                        $newFieldDef = null;
                        if (isset($field["definition"]))
                            $newFieldDef = $field["definition"];
                        $oldFieldType = null;
                        $oldFieldDef = null;
                        $oldFieldVal = null;
                        if (!$typeChanged) {
                            foreach ($oldDef["fields"] as $oldField) {
                                if ($oldField["name"] == $field["name"]) {
                                    $oldFieldType = $oldField["type"];
                                    if (is_array($oldField) && isset($oldField["definition"]))
                                        $oldFieldDef = $oldField["definition"];
                                    if (is_array($oldVal) && isset($oldVal[$oldField["name"]]))
                                        $oldFieldVal = $oldVal[$oldField["name"]];
                                    break;
                                }
                            }
                        }
                        self::mergeValue($field["type"], $oldFieldType, $newFieldDef, $oldFieldDef, $newFieldVal, $oldFieldVal, $dstFieldVal, $allowDefault);
                    }
                    break;
                //list type
                case 10:
                    if ($mergedVal !== null) {
                        for ($i = 0; $i < count($mergedVal); $i++) {

                            //invalid data check
                            if (!isset($mergedVal[$i])) continue;

                            $oldElemType = null;
                            $oldElemDef = null;
                            $oldElemVal = null;
                            if (!$typeChanged) {
                                $oldElemType = $oldDef["element"]["type"];
                                if (isset($oldDef["element"]["definition"]))
                                    $oldElemDef = $oldDef["element"]["definition"];
                                if ($oldVal !== null && count($oldVal) > $i) {
                                    $oldElemVal = $oldVal[$i];
                                }
                                $newElemVal = null;
                                if ($newVal !== null && count($newVal) > $i) {
                                    $newElemVal = $newVal[$i];
                                }
                            }
                            self::mergeValue($newDef["element"]["type"], $oldElemType, $newDef["element"]["definition"], $oldElemDef, $newElemVal, $oldElemVal, $mergedVal[$i], $allowDefault);
                        }
                    }
                    break;
            }
        }
    }

    public function authorizeObject($object)
    {
        if (!self::$securityOn)
            return $object;
        if ($object && $this->securityAuthorizationChecker->isGranted(ObjectVoter::ATTR_ACCESS, $object->getWizard()))
            return $object;
        return null;
    }

    public function onObjectRename($object, $oldName)
    {
        foreach ($this->testWizardRepository->findAll() as $wizard) {
            foreach ($wizard->getParams() as $param) {
                $def = $param->getDefinition();
                $type = $param->getType();
                $paramValue = $param->getValue();
                if (self::modifyPropertiesOnRename($object->getName(), get_class($object), $oldName, $type, $def, $paramValue)) {
                    $oldParam = clone $param;
                    $param->setDefinition($def);
                    if (is_array($paramValue)) $paramValue = json_encode($paramValue);
                    $param->setValue($paramValue);
                    $this->update($param, $oldParam);
                }

                foreach ($wizard->getResultingTests() as $test) {
                    foreach ($test->getVariables() as $var) {
                        if ($var->getParentVariable()->getId() == $param->getVariable()->getId()) {
                            $varValue = $var->getValue();
                            if (self::modifyPropertiesOnRename($object->getName(), get_class($object), $oldName, $type, $def, $varValue, true)) {
                                if (is_array($varValue)) $varValue = json_encode($varValue);
                                $var->setValue($varValue);
                                $this->testVariableService->update($var);
                            }

                            //ports
                            foreach ($var->getPorts() as $port) {
                                $portValue = $port->getValue();

                                if (self::modifyPropertiesOnRename($object->getName(), get_class($object), $oldName, $type, $def, $portValue, true)) {
                                    if (is_array($portValue)) $portValue = json_encode($portValue);
                                    $port->setValue($portValue);
                                    $this->testNodePortService->update($port);
                                }
                            }
                            break;
                        }
                    }
                }
            }
        }
    }

    public static function modifyPropertiesOnRename($newName, $class, $oldName, $type, &$def, &$val, $onlyVal = false)
    {
        $moded = false;

        //ViewTemplate
        if ($type === 5 && $class === ViewTemplate::class) {
            if ($val == $oldName) {
                $moded = true;
                $val = $newName;
            }
            if (!$onlyVal && isset($def["defvalue"]) && $def["defvalue"] == $oldName) {
                $moded = true;
                $def["defvalue"] = $newName;
            }
        }
        //DataTable
        if ($type === 6 && $class === DataTable::class) {
            if ($val == $oldName) {
                $moded = true;
                $val = $newName;
            }
            if (!$onlyVal && isset($def["defvalue"]) && $def["defvalue"] == $oldName) {
                $moded = true;
                $def["defvalue"] = $newName;
            }
        }
        //DataTable column
        if ($type == 7 && $class === DataTable::class) {
            if (!is_array($val))
                $val = json_decode($val, true);
            if (isset($val["table"]) && $val["table"] == $oldName) {
                $moded = true;
                $val["table"] = $newName;
            }
        }
        //Test
        if ($type === 8 && $class === Test::class) {
            if ($val == $oldName) {
                $moded = true;
                $val = $newName;
            }
            if (!$onlyVal && isset($def["defvalue"]) && $def["defvalue"] == $oldName) {
                $moded = true;
                $def["defvalue"] = $newName;
            }
        }
        //Group
        if ($type == 9) {
            if (!is_array($val))
                $val = json_decode($val, true);
            if (isset($def["fields"])) {
                for ($i = 0; $i < count($def["fields"]); $i++) {
                    $field = $def["fields"][$i];
                    $moded |= self::modifyPropertiesOnRename($newName, $class, $oldName, $field["type"], $def["fields"][$i]["definition"], $val[$field["name"]], $onlyVal);
                }
            }
        }
        //List
        if ($type == 10) {
            if (!is_array($val))
                $val = json_decode($val, true);
            if ($val !== null && isset($def["element"]) && isset($def["element"]["definition"])) {
                for ($i = 0; $i < count($val); $i++) {
                    $moded |= self::modifyPropertiesOnRename($newName, $class, $oldName, $def["element"]["type"], $def["element"]["definition"], $val[$i], $onlyVal);
                }
            }
        }
        //DataTable map
        if ($type === 12 && $class === DataTable::class) {
            if (!is_array($val))
                $val = json_decode($val, true);
            if (isset($val["table"]) && $val["table"] == $oldName) {
                $moded = true;
                $val["table"] = $newName;
            }
        }
        //Nested wizard
        if ($type == 13 && $class === Test::class) {
            if (!is_array($val))
                $val = json_decode($val, true);
            if (isset($val["test"]) && $val["test"] == $oldName) {
                $moded = true;
                $val["test"] = $newName;
            }
        }
        return $moded;
    }
}
