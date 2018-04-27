<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Repository\TestNodeConnectionRepository;
use Concerto\PanelBundle\Entity\Test;
use Concerto\PanelBundle\Entity\TestNodeConnection;
use Concerto\PanelBundle\Entity\TestNode;
use Concerto\PanelBundle\Entity\TestVariable;
use Concerto\PanelBundle\Entity\User;
use Concerto\PanelBundle\Repository\TestRepository;
use Concerto\PanelBundle\Repository\TestNodeRepository;
use Concerto\PanelBundle\Repository\TestNodePortRepository;
use Concerto\PanelBundle\Security\ObjectVoter;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TestNodeConnectionService extends ASectionService
{

    private $validator;
    private $testRepository;
    private $testNodeRepository;
    private $testNodePortRepository;

    public function __construct(TestNodeConnectionRepository $repository, ValidatorInterface $validator, TestRepository $testRepository, TestNodeRepository $testNodeRepository, TestNodePortRepository $testNodePortRepository, AuthorizationCheckerInterface $securityAuthorizationChecker)
    {
        parent::__construct($repository, $securityAuthorizationChecker);

        $this->validator = $validator;
        $this->testRepository = $testRepository;
        $this->testNodeRepository = $testNodeRepository;
        $this->testNodePortRepository = $testNodePortRepository;
    }

    public function get($object_id, $createNew = false, $secure = true)
    {
        $object = parent::get($object_id, $createNew, $secure);
        if ($createNew && $object === null) {
            $object = new TestNodeConnection();
        }
        return $object;
    }

    public function getByFlowTest($test_id)
    {
        return $this->authorizeCollection($this->repository->findByFlowTest($test_id));
    }

    public function save(User $user, $object_id, Test $flowTest, TestNode $sourceNode, $sourcePort, TestNode $destinationNode, $destinationPort, $returnFunction, $automatic, $default)
    {
        $errors = array();
        $object = $this->get($object_id);
        $is_new = false;
        if ($object === null) {
            $object = new TestNodeConnection();
            $is_new = true;
        }
        $object->setUpdated();
        $object->setFlowTest($flowTest);
        $object->setSourceNode($sourceNode);
        $object->setSourcePort($sourcePort);
        $object->setDestinationNode($destinationNode);
        $object->setDestinationPort($destinationPort);
        $object->setDefaultReturnFunction($default);
        if ($default || !$returnFunction) {
            if (!$sourcePort) {
                $object->setReturnFunction("");
            } else {
                $object->setReturnFunction($sourcePort->getVariable()->getName());
            }
        } else {
            $object->setReturnFunction($returnFunction);
        }
        $object->setAutomatic($automatic);

        foreach ($this->validator->validate($object) as $err) {
            array_push($errors, $err->getMessage());
        }
        if (count($errors) > 0) {
            return array("object" => null, "errors" => $errors);
        }
        $this->repository->save($object);
        $this->onObjectSaved($user, $is_new, $object);

        return array("object" => $object, "errors" => $errors);
    }

    public function onObjectSaved(User $user, $is_new, TestNodeConnection $object)
    {
        if ($is_new) {
            $this->addSameInputReturnConnection($user, $object);
        }
    }

    private function addSameInputReturnConnection(User $user, TestNodeConnection $object)
    {
        if (!$object->getSourcePort() || $object->getSourcePort()->getVariable()->getType() == 2) {
            $srcNode = $object->getSourceNode();
            $dstNode = $object->getDestinationNode();

            foreach ($srcNode->getPorts() as $srcPort) {
                $srcVar = $srcPort->getVariable();
                if (!$srcVar) {
                    continue;
                }

                if ($srcVar->getType() == 1) {
                    foreach ($dstNode->getPorts() as $dstPort) {
                        $dstVar = $dstPort->getVariable();
                        if (!$dstVar) {
                            continue;
                        }

                        if ($dstVar->getType() == 0 && $srcPort->getVariable()->getName() == $dstPort->getVariable()->getName()) {
                            $this->save($user, 0, $object->getFlowTest(), $srcNode, $srcPort, $dstNode, $dstPort, $srcPort->getVariable()->getName(), true, true);
                            break;
                        }
                    }
                }
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
                $this->onObjectDeleted($object);
                array_push($result, array("object" => $object, "errors" => array()));
            }
        }
        return $result;
    }

    public function onObjectDeleted(TestNodeConnection $object)
    {
        $this->repository->deleteAutomatic($object->getSourceNode(), $object->getDestinationNode());
    }

    public function onTestVariableSaved(User $user, TestVariable $variable, $is_new, $flush = true)
    {
        $ports = $variable->getPorts();
        foreach ($ports as $port) {
            $connections = $port->getSourceForConnections();
            foreach ($connections as $connection) {
                if ($connection->getReturnFunction() != $variable->getName() && $connection->hasDefaultReturnFunction()) {
                    $connection->setReturnFunction($variable->getName());
                    $this->repository->save($connection, $flush);
                }
            }
        }
    }

    public function importFromArray(User $user, $instructions, $obj, &$map, &$queue)
    {
        $pre_queue = array();
        if (!array_key_exists("TestNodeConnection", $map))
            $map["TestNodeConnection"] = array();
        if (array_key_exists("id" . $obj["id"], $map["TestNodeConnection"]))
            return array("errors" => null, "entity" => $map["TestNodeConnection"]["id" . $obj["id"]]);

        $flowTest = null;
        if (array_key_exists("Test", $map) && array_key_exists("id" . $obj["flowTest"], $map["Test"])) {
            $flowTest = $map["Test"]["id" . $obj["flowTest"]];
        }

        $sourceNode = null;
        if (array_key_exists("TestNode", $map) && array_key_exists("id" . $obj["sourceNode"], $map["TestNode"])) {
            $sourceNode = $map["TestNode"]["id" . $obj["sourceNode"]];
        }

        $destinationNode = null;
        if (array_key_exists("TestNode", $map) && array_key_exists("id" . $obj["destinationNode"], $map["TestNode"])) {
            $destinationNode = $map["TestNode"]["id" . $obj["destinationNode"]];
        }

        $sourcePort = null;
        if ($obj["sourcePort"]) {
            if (array_key_exists("TestNodePort", $map) && array_key_exists("id" . $obj["sourcePort"], $map["TestNodePort"])) {
                $sourcePort = $map["TestNodePort"]["id" . $obj["sourcePort"]];
            }
            if (!$sourcePort) {
                array_push($pre_queue, $obj["sourcePortObject"]);
            }
        }

        $destinationPort = null;
        if ($obj["destinationPort"]) {
            if (array_key_exists("TestNodePort", $map) && array_key_exists("id" . $obj["destinationPort"], $map["TestNodePort"])) {
                $destinationPort = $map["TestNodePort"]["id" . $obj["destinationPort"]];
            }
            if (!$destinationPort) {
                array_push($pre_queue, $obj["destinationPortObject"]);
            }
        }

        if (count($pre_queue) > 0) {
            return array("pre_queue" => $pre_queue);
        }

        $parent_instruction = self::getObjectImportInstruction(array(
            "class_name" => "Test",
            "id" => $obj["flowTest"]
        ), $instructions);
        $result = array();
        $src_ent = $this->findConversionSource($obj, $map);
        if ($parent_instruction["action"] == 2 && $src_ent) {
            $map["TestNodeConnection"]["id" . $obj["id"]] = $src_ent;
            $result = array("errors" => null, "entity" => $src_ent);
        } else
            $result = $this->importNew($user, null, $obj, $map, $queue, $destinationNode, $destinationPort, $flowTest, $sourcePort, $sourceNode);
        return $result;
    }

    protected function findConversionSource($obj, $map)
    {
        $sourcePortId = null;
        if ($obj["sourcePort"])
            $sourcePortId = $map["TestNodePort"]["id" . $obj["sourcePort"]]->getId();
        $destinationPortId = null;
        if ($obj["destinationPort"])
            $destinationPortId = $map["TestNodePort"]["id" . $obj["destinationPort"]]->getId();
        $ent = $this->repository->findByPorts($sourcePortId, $destinationPortId);
        if (!$ent)
            return null;
        return $this->get($ent->getId());
    }

    protected function importNew(User $user, $new_name, $obj, &$map, &$queue, $destinationNode, $destinationPort, $flowTest, $sourcePort, $sourceNode)
    {
        $ent = new TestNodeConnection();
        $ent->setDestinationNode($destinationNode);
        $ent->setDestinationPort($destinationPort);
        $ent->setFlowTest($flowTest);
        $ent->setReturnFunction($obj["returnFunction"]);
        $ent->setSourceNode($sourceNode);
        $ent->setSourcePort($sourcePort);
        $ent->setAutomatic($obj["automatic"] == "1");
        if (array_key_exists("defaultReturnFunction", $obj))
            $ent->setDefaultReturnFunction($obj["defaultReturnFunction"]);
        else
            $ent->setDefaultReturnFunction($sourcePort->getVariable()->getName() == $obj["returnFunction"]);
        $ent_errors = $this->validator->validate($ent);
        $ent_errors_msg = array();
        foreach ($ent_errors as $err) {
            array_push($ent_errors_msg, $err->getMessage());
        }
        if (count($ent_errors_msg) > 0) {
            return array("errors" => $ent_errors_msg, "entity" => null, "source" => $obj);
        }
        $this->repository->save($ent, false);
        $map["TestNodeConnection"]["id" . $obj["id"]] = $ent;
        return array("errors" => null, "entity" => $ent);
    }

    public function authorizeObject($object)
    {
        if (!self::$securityOn)
            return $object;
        if ($object && $this->securityAuthorizationChecker->isGranted(ObjectVoter::ATTR_ACCESS, $object->getFlowTest()))
            return $object;
        return null;
    }

}
