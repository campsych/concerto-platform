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
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TestNodePortService extends ASectionService
{

    private $validator;
    private $testVariableRepository;
    private $testNodeRepository;
    private $logger;

    public function __construct(TestNodePortRepository $repository, ValidatorInterface $validator, TestVariableRepository $testVariableRepository, TestNodeRepository $testNodeRepository, AuthorizationCheckerInterface $securityAuthorizationChecker, LoggerInterface $logger)
    {
        parent::__construct($repository, $securityAuthorizationChecker);

        $this->validator = $validator;
        $this->testVariableRepository = $testVariableRepository;
        $this->testNodeRepository = $testNodeRepository;
        $this->logger = $logger;
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
        return $this->authorizeObject($this->repository->findOneByNodeAndVariable($node, $variable));
    }

    public function save(User $user, $object_id, TestNode $node, TestVariable $variable, $default, $value, $string, $flush = true)
    {
        $errors = array();
        $object = $this->get($object_id);
        if ($object === null) {
            $object = new TestNodePort();
        }
        $object->setUpdated();
        $object->setNode($node);
        $object->setVariable($variable);
        $object->setDefaultValue($default);
        if ($default) {
            $object->setValue($variable->getValue());
        } else {
            $object->setValue($value);
        }
        $object->setString($string);

        foreach ($this->validator->validate($object) as $err) {
            array_push($errors, $err->getMessage());
        }
        if (count($errors) > 0) {
            return array("object" => null, "errors" => $errors);
        }
        $this->repository->save($object, $flush);

        return array("object" => $object, "errors" => $errors);
    }

    public function saveCollection(User $user, $encoded_collection)
    {
        $decoded_collection = json_decode($encoded_collection, true);
        $result = array("errors" => array());
        for ($i = 0; $i < count($decoded_collection); $i++) {
            $port = $decoded_collection[$i];
            $node = $this->testNodeRepository->find($port["node"]);
            $variable = $this->testVariableRepository->find($port["variable"]);
            $r = $this->save($user, $port["id"], $node, $variable, $port["defaultValue"], array_key_exists("value", $port) ? $port["value"] : null, $port["string"]);
            if (count($r["errors"]) > 0) {
                for ($a = 0; $a < count($r["errors"]); $a++) {
                    array_push($result["errors"], $r["errors"][$a]);
                }
            }
        }
        return $result;
    }

    public function update($object, $flush = true)
    {
        $this->repository->save($object, $flush);
    }

    public function onTestVariableSaved(User $user, TestVariable $variable, $is_new, $flush = true)
    {
        $nodes = $variable->getTest()->getSourceForNodes();
        foreach ($nodes as $node) {
            $ports = $node->getPorts();
            $found = false;
            foreach ($ports as $port) {
                if ($port->getVariable()->getId() == $variable->getId()) {
                    $found = true;
                    if ($port->hasDefaultValue()) {
                        $port->setValue($variable->getValue());
                        $this->update($port, $flush);
                    }
                    break;
                }
            }
            if (!$found) {
                $result = $this->save($user, 0, $node, $variable, true, $variable->getValue(), true, $flush);
                $node->addPort($result["object"]);
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

    public function importFromArray(User $user, $instructions, $obj, &$map, &$queue)
    {
        $pre_queue = array();
        if (!array_key_exists("TestNodePort", $map))
            $map["TestNodePort"] = array();
        if (array_key_exists("id" . $obj["id"], $map["TestNodePort"])) {
            return array("errors" => null, "entity" => $map["TestNodePort"]["id" . $obj["id"]]);
        }

        $node = null;
        if (array_key_exists("TestNode", $map) && array_key_exists("id" . $obj["node"], $map["TestNode"])) {
            $node = $map["TestNode"]["id" . $obj["node"]];
        }

        $variable = null;
        if ($obj["variable"]) {
            if (array_key_exists("TestVariable", $map) && array_key_exists("id" . $obj["variable"], $map["TestVariable"])) {
                $variable = $map["TestVariable"]["id" . $obj["variable"]];
            }
            if (!$variable) {
                array_push($pre_queue, $obj["variableObject"]);
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
        $result = array();
        $src_ent = $this->findConversionSource($obj, $map);
        if ($parent_instruction["action"] == 1 && $src_ent) {
            $result = $this->importConvert($user, null, $src_ent, $obj, $map, $queue, $node, $variable);
        } else if ($parent_instruction["action"] == 2 && $src_ent) {
            $map["TestNodePort"]["id" . $obj["id"]] = $src_ent;
            $result = array("errors" => null, "entity" => $src_ent);
        } else
            $result = $this->importNew($user, null, $obj, $map, $queue, $node, $variable);
        return $result;
    }

    protected function importConvert(User $user, $new_name, $src_ent, $obj, &$map, &$queue, $node, $variable)
    {
        $old_ent = clone $src_ent;
        $ent = $src_ent;
        $ent->setNode($node);
        $ent->setValue($obj["value"]);
        $ent->setVariable($variable);
        $ent->setDefaultValue($obj["defaultValue"] == "1");
        $ent->setString($obj["string"] == "1");
        $ent_errors = $this->validator->validate($ent);
        $ent_errors_msg = array();
        foreach ($ent_errors as $err) {
            array_push($ent_errors_msg, $err->getMessage());
        }
        if (count($ent_errors_msg) > 0) {
            return array("errors" => $ent_errors_msg, "entity" => null, "source" => $obj);
        }
        $this->repository->save($ent, false);
        $map["TestNodePort"]["id" . $obj["id"]] = $ent;
        return array("errors" => null, "entity" => $ent);
    }

    protected function findConversionSource($obj, $map)
    {
        $node = $map["TestNode"]["id" . $obj["node"]];
        $variable = $map["TestVariable"]["id" . $obj["variable"]];
        $ent = $this->repository->findOneBy(array("node" => $node, "variable" => $variable));
        if (!$ent) {
            return null;
        }
        return $this->get($ent->getId());
    }

    protected function importNew(User $user, $new_name, $obj, &$map, &$queue, $node, $variable)
    {
        $ent = new TestNodePort();
        $ent->setNode($node);
        $ent->setValue($obj["value"]);
        $ent->setVariable($variable);
        $ent->setDefaultValue($obj["defaultValue"] == "1");
        $ent->setString($obj["string"] == "1");
        $ent_errors = $this->validator->validate($ent);
        $ent_errors_msg = array();
        foreach ($ent_errors as $err) {
            array_push($ent_errors_msg, $err->getMessage());
        }
        if (count($ent_errors_msg) > 0) {
            return array("errors" => $ent_errors_msg, "entity" => null, "source" => $obj);
        }
        $this->repository->save($ent, false);
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

}
