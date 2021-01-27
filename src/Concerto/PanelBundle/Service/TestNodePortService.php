<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Entity\User;
use Concerto\PanelBundle\Repository\TestNodePortRepository;
use Concerto\PanelBundle\Entity\TestNodePort;
use Concerto\PanelBundle\Entity\TestNode;
use Concerto\PanelBundle\Entity\TestVariable;
use Concerto\PanelBundle\Repository\TestVariableRepository;
use Concerto\PanelBundle\Repository\TestNodeRepository;
use Concerto\PanelBundle\Security\ObjectVoter;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TestNodePortService extends ASectionService
{

    private $validator;
    private $testVariableRepository;
    private $testNodeRepository;
    private $testNodeConnectionService;

    public function __construct(
        TestNodePortRepository $repository,
        ValidatorInterface $validator,
        TestVariableRepository $testVariableRepository,
        TestNodeRepository $testNodeRepository,
        AuthorizationCheckerInterface $securityAuthorizationChecker,
        TestNodeConnectionService $testNodeConnectionService,
        TokenStorageInterface $securityTokenStorage,
        AdministrationService $administrationService,
        LoggerInterface $logger)
    {
        parent::__construct($repository, $securityAuthorizationChecker, $securityTokenStorage, $administrationService, $logger);

        $this->validator = $validator;
        $this->testVariableRepository = $testVariableRepository;
        $this->testNodeRepository = $testNodeRepository;
        $this->testNodeConnectionService = $testNodeConnectionService;
    }

    public function get($object_id, $createNew = false, $secure = true)
    {
        $object = parent::get($object_id, $createNew, $secure);
        if ($createNew && $object === null) {
            $object = new TestNodePort();
        }
        return $object;
    }

    public function getOneByNodeAndVariable(TestNode $node, TestVariable $variable)
    {
        return $this->authorizeObject($node->getPortByVariable($variable));
    }

    public function save($object_id, TestNode $node, ?TestVariable $variable, $default, $value, $string, $type, $dynamic, $exposed, $name, $pointer, $pointerVariable, $flush = true)
    {
        $errors = array();
        $object = $this->get($object_id);
        if ($object === null) {
            $object = new TestNodePort();
        }
        $object->setNode($node);
        $object->setVariable($variable);

        if ($type === null) {
            $type = $variable->getType();
            if ($node->getType() == 1 && $variable->getType() == 0) $type = 1;
            if ($node->getType() == 2 && $variable->getType() == 1) $type = 0;
        }
        $object->setType($type);
        $object->setDynamic($dynamic);
        $object->setExposed($exposed);
        if ($name === null) $object->setName($variable->getName());
        else $object->setName($name);

        $object->setDefaultValue($default);
        if ($default && $variable) {
            $object->setValue($variable->getValue());
        } else {
            $object->setValue($value);
        }
        $object->setString($string);
        if ($pointer !== null) {
            $object->setPointer($pointer);
        } else {
            $object->setPointerVariable($object->getName());
        }
        if ($pointerVariable !== null) {
            $object->setPointerVariable($pointerVariable);
        }

        foreach ($this->validator->validate($object) as $err) {
            array_push($errors, $err->getMessage());
        }
        if (count($errors) > 0) {
            return array("object" => null, "errors" => $errors);
        }
        $this->update($object, $flush);

        return array("object" => $object, "errors" => $errors);
    }

    private function onObjectSaved(TestNodePort $obj, $isNew)
    {
        if (!$isNew) {
            $this->testNodeConnectionService->updateDefaultReturnFunctions($obj);
        }
    }

    public function saveCollection($encoded_collection)
    {
        $decoded_collection = json_decode($encoded_collection, true);
        $result = array("errors" => array());
        for ($i = 0; $i < count($decoded_collection); $i++) {
            $port = $decoded_collection[$i];
            $node = $this->testNodeRepository->find($port["node"]);
            $variable = null;
            if ($port["variable"] !== null) {
                $variable = $this->testVariableRepository->find($port["variable"]);
            }
            $r = $this->save($port["id"], $node, $variable, $port["defaultValue"], isset($port["value"]) ? $port["value"] : null, $port["string"], $port["type"], $port["dynamic"], $port["exposed"], $port["name"], $port["pointer"], $port["pointerVariable"]);
            if (count($r["errors"]) > 0) {
                for ($a = 0; $a < count($r["errors"]); $a++) {
                    array_push($result["errors"], $r["errors"][$a]);
                }
            }
        }
        return $result;
    }

    public function update(TestNodePort $object, $flush = true)
    {
        $isNew = $object->getId() === null;
        $changeSet = $this->repository->getChangeSet($object);
        if ($isNew || !empty($changeSet)) {
            $this->repository->save($object, $flush);
            $this->onObjectSaved($object, $isNew);
        }
    }

    public function onTestVariableSaved(TestVariable $variable, $is_new, $flush = true)
    {
        $nodes = $variable->getTest()->getSourceForNodes();

        foreach ($nodes as $node) {
            $ports = $node->getPorts();
            $found = false;
            /** @var TestNodePort $port */
            foreach ($ports as $port) {
                if (($port->getVariable() && $variable->getId() && $port->getVariable()->getId() == $variable->getId()) ||
                    ($port->getType() == $variable->getType() && $port->getName() == $variable->getName() && $port->isDynamic())) {

                    $found = true;
                    $updateNeeded = false;

                    if ($port->isDynamic()) {
                        $port->setExposed(true);
                        $port->setDynamic(false);
                        $port->setVariable($variable);
                        $updateNeeded = true;
                    }

                    $changeValue = $port->hasDefaultValue() && $port->getValue() !== $variable->getValue();
                    if ($changeValue) {
                        $port->setValue($variable->getValue());
                        $updateNeeded = true;
                    }

                    $changeName = $port->getName() !== $variable->getName();
                    if ($changeName) {
                        $port->setName($variable->getName());
                        $updateNeeded = true;
                    }

                    if ($updateNeeded) {
                        $this->update($port, $flush);
                    }
                    break;
                }
            }

            if (!$found) {
                if ($node->getType() == 1) {
                    if ($variable->getType() == 1 || $variable->getType() == 2) continue;
                }
                if ($node->getType() == 2) {
                    if ($variable->getType() == 0 || $variable->getType() == 2) continue;
                }
                $exposed = $variable->getType() == 2;
                $result = $this->save(0, $node, $variable, true, $variable->getValue(), true, null, false, $exposed, null, null, null, $flush);
            }
        }
    }

    public function delete($object_ids, $secure = true)
    {
        $object_ids = explode(",", $object_ids);

        $result = array();
        foreach ($object_ids as $object_id) {
            $object = $this->get($object_id, false, $secure);
            if ($object) {
                $this->repository->delete($object);
                array_push($result, array("object" => $object, "errors" => array()));
            }
        }
        return $result;
    }

    public function importFromArray($instructions, $obj, &$map, &$renames, &$queue)
    {
        $pre_queue = array();
        if (!isset($map["TestNodePort"]))
            $map["TestNodePort"] = array();
        if (isset($map["TestNodePort"]["id" . $obj["id"]])) {
            return array("errors" => null, "entity" => $map["TestNodePort"]["id" . $obj["id"]]);
        }

        $node = null;
        if (isset($map["TestNode"]) && isset($map["TestNode"]["id" . $obj["node"]])) {
            $node = $map["TestNode"]["id" . $obj["node"]];
        }

        $variable = null;
        if ($obj["variable"]) {
            if (isset($map["TestVariable"]) && isset($map["TestVariable"]["id" . $obj["variable"]])) {
                $variable = $map["TestVariable"]["id" . $obj["variable"]];
            }
        }

        if (count($pre_queue) > 0) {
            return array("pre_queue" => $pre_queue);
        }

        $imported_parent_id = $node->getFlowTest()->getId();
        $exported_parent_id = null;
        foreach ($map["Test"] as $k => $v) {
            if ($v->getId() == $imported_parent_id) {
                $exported_parent_id = substr($k, 2);
                break;
            }
        }
        $parent_instruction = self::getObjectImportInstruction(array(
            "class_name" => "Test",
            "id" => $exported_parent_id
        ), $instructions);

        //port should never be converted
        if ($parent_instruction["action"] == 0 || $parent_instruction["action"] == 1) { //new or convert
            return $this->importNew(null, $obj, $map, $renames, $queue, $node, $variable);
        }
        return null;
    }

    protected function importNew($new_name, $obj, &$map, $renames, &$queue, $node, $variable)
    {
        $ent = new TestNodePort();
        $ent->setNode($node);
        $ent->setValue($obj["value"]);
        $ent->setVariable($variable);
        $ent->setDefaultValue($obj["defaultValue"] == "1");
        $ent->setString($obj["string"] == "1");
        $ent->setDynamic($obj["dynamic"] == "1");
        $ent->setType($obj["type"]);
        $ent->setExposed($obj["exposed"] == "1");
        $ent->setName($obj["name"]);
        if (isset($obj["pointer"])) {
            $ent->setPointer($obj["pointer"]);
        } else {
            $ent->setPointer($ent->getName());
        }
        if (isset($obj["pointerVariable"])) {
            $ent->setPointerVariable($obj["pointerVariable"]);
        }

        if ($variable) {
            if ($test = $variable->getTest()) {
                $wizard = $test->getSourceWizard();
                $parentVariable = $variable->getParentVariable();
                if ($wizard && $parentVariable) {
                    foreach ($wizard->getParams() as $param) {
                        if ($param->getVariable()->getId() === $parentVariable->getId()) {
                            $val = $ent->getValue();
                            foreach ($renames as $class => $renameMap) {
                                foreach ($renameMap as $oldName => $newName) {
                                    $def = $param->getDefinition();
                                    $moded = TestWizardParamService::modifyPropertiesOnRename($newName, $class, $oldName, $param->getType(), $def, $val, true);
                                    if ($moded) {
                                        $ent->setValue($val);
                                    }
                                }
                            }
                            break;
                        }
                    }
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
        $this->update($ent, false);
        $map["TestNodePort"]["id" . $obj["id"]] = $ent;
        return array("errors" => null, "entity" => $ent);
    }

    public function authorizeObject($object)
    {
        if (!self::$securityOn)
            return $object;
        if ($object && $this->securityAuthorizationChecker->isGranted(ObjectVoter::ATTR_ACCESS, $object->getNode()))
            return $object;
        return null;
    }

    public function exposePorts($ports)
    {
        foreach ($ports as $port) {
            $obj = $this->get($port["id"]);
            if (!$obj) continue;
            $obj->setExposed($port["exposed"] == 1);
            $this->update($obj);
        }
    }

    public function addDynamic(TestNode $node, $name, $type)
    {
        return $this->save(
            0,
            $node,
            null,
            true,
            "",
            true,
            $type,
            true,
            true,
            $name,
            null,
            null
        );
    }

    public function hide($id)
    {
        $port = $this->get($id);
        if ($port) {
            if ($port->isDynamic()) {
                $this->delete($id);
            } else {
                $port->setExposed(false);
                $this->update($port);
            }
        }
    }
}
