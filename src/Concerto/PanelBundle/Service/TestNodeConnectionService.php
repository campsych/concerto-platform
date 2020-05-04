<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Repository\TestNodeConnectionRepository;
use Concerto\PanelBundle\Entity\Test;
use Concerto\PanelBundle\Entity\TestNodeConnection;
use Concerto\PanelBundle\Entity\TestNode;
use Concerto\PanelBundle\Entity\TestNodePort;
use Concerto\PanelBundle\Entity\TestVariable;
use Concerto\PanelBundle\Entity\User;
use Concerto\PanelBundle\Repository\TestRepository;
use Concerto\PanelBundle\Repository\TestNodeRepository;
use Concerto\PanelBundle\Repository\TestNodePortRepository;
use Concerto\PanelBundle\Security\ObjectVoter;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TestNodeConnectionService extends ASectionService
{

    private $validator;
    private $testRepository;
    private $testNodeRepository;
    private $testNodePortRepository;
    private $testNodeConnectionRepository;

    public function __construct(
        TestNodeConnectionRepository $repository,
        ValidatorInterface $validator,
        TestRepository $testRepository,
        TestNodeRepository $testNodeRepository,
        TestNodePortRepository $testNodePortRepository,
        AuthorizationCheckerInterface $securityAuthorizationChecker,
        TokenStorageInterface $securityTokenStorage,
        TestNodeConnectionRepository $testNodeConnectionRepository
    )
    {
        parent::__construct($repository, $securityAuthorizationChecker, $securityTokenStorage);

        $this->validator = $validator;
        $this->testRepository = $testRepository;
        $this->testNodeRepository = $testNodeRepository;
        $this->testNodePortRepository = $testNodePortRepository;
        $this->testNodeConnectionRepository = $testNodeConnectionRepository;
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

    public function save($object_id, Test $flowTest, TestNode $sourceNode, $sourcePort, TestNode $destinationNode, $destinationPort, $returnFunction, $automatic, $default)
    {
        $errors = array();
        $object = $this->get($object_id);
        if ($object === null) {
            $object = new TestNodeConnection();
        }
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
                $object->setReturnFunction($sourcePort->getName());
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
        $this->update($object);

        return array("object" => $object, "errors" => $errors);
    }

    private function onObjectSaved(TestNodeConnection $object, $isNew)
    {
        if ($isNew) {
            $this->addSameInputReturnConnection($object);
        }
    }

    private function update(TestNodeConnection $object, $flush = true)
    {
        $user = null;
        $token = $this->securityTokenStorage->getToken();
        if ($token !== null) $user = $token->getUser();

        $object->setUpdatedBy($user);
        $isNew = $object->getId() === null;
        $this->repository->save($object, $flush);

        $this->onObjectSaved($object, $isNew);
    }

    public function updateDefaultReturnFunctions(TestNodePort $sourcePort)
    {
        $connections = $sourcePort->getSourceForConnections()->filter(function (TestNodeConnection $connection) {
            return $connection->hasDefaultReturnFunction();
        });

        foreach ($connections as $connection) {
            $connection->setReturnFunction($sourcePort->getName());
            $this->update($connection);
        }
    }

    private function isPairEligibleForAutoConnection(TestNodePort $src, TestNodePort $dst)
    {
        if ($src->getType() != 1 || $dst->getType() != 0) return false;
        if ($src->getName() != $dst->getName()) return false;
        if ($src->getNode()->getType() == 0 && !$src->isDynamic() && !$src->isExposed()) return false;
        if ($dst->getNode()->getType() == 0 && !$dst->isDynamic() && !$dst->isExposed()) return false;
        if ($dst->isPointer()) return false;
        return true;
    }

    private function addSameInputReturnConnection(TestNodeConnection $object)
    {
        if (!$object->getSourcePort() || $object->getSourcePort()->getType() == 2) {
            $srcNode = $object->getSourceNode();
            $dstNode = $object->getDestinationNode();

            foreach ($srcNode->getPorts() as $srcPort) {
                if ($srcPort->getType() == 1) {
                    foreach ($dstNode->getPorts() as $dstPort) {
                        if ($this->isPairEligibleForAutoConnection($srcPort, $dstPort)) {
                            $this->save(0, $object->getFlowTest(), $srcNode, $srcPort, $dstNode, $dstPort, $srcPort->getName(), true, true);
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

    public function onTestVariableSaved(TestVariable $variable, $is_new, $flush = true)
    {
        $connections = $variable->getTest()->getNodesConnections()->filter(function (TestNodeConnection $connection, TestVariable $variable) {
            return $connection->getSourcePort() && $connection->getSourcePort()->getVariable() && $connection->getSourcePort()->getVariable()->getId() === $variable->getId();
        });

        foreach ($connections as $connection) {
            if ($connection->getReturnFunction() != $variable->getName() && $connection->hasDefaultReturnFunction()) {
                $connection->setReturnFunction($variable->getName());
                $this->update($connection, $flush);
            }
        }
    }

    public function importFromArray($instructions, $obj, &$map, &$renames, &$queue)
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
        }

        $destinationPort = null;
        if ($obj["destinationPort"]) {
            if (array_key_exists("TestNodePort", $map) && array_key_exists("id" . $obj["destinationPort"], $map["TestNodePort"])) {
                $destinationPort = $map["TestNodePort"]["id" . $obj["destinationPort"]];
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
        $src_ent = null; //connection should never be converted
        if ($parent_instruction["action"] == 2 && $src_ent) {
            $map["TestNodeConnection"]["id" . $obj["id"]] = $src_ent;
            $result = array("errors" => null, "entity" => $src_ent);
        } else
            $result = $this->importNew(null, $obj, $map, $queue, $destinationNode, $destinationPort, $flowTest, $sourcePort, $sourceNode);
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

    protected function importNew($new_name, $obj, &$map, &$queue, $destinationNode, $destinationPort, $flowTest, $sourcePort, $sourceNode)
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
        $this->update($ent, false);
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
