<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Repository\TestNodeConnectionRepository;
use Concerto\PanelBundle\Entity\Test;
use Concerto\PanelBundle\Entity\TestNodeConnection;
use Concerto\PanelBundle\Entity\TestNode;
use Concerto\PanelBundle\Entity\TestNodePort;
use Concerto\PanelBundle\Entity\User;
use Symfony\Component\Validator\Validator\RecursiveValidator;
use Concerto\PanelBundle\Repository\TestRepository;
use Concerto\PanelBundle\Repository\TestNodeRepository;
use Concerto\PanelBundle\Repository\TestNodePortRepository;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class TestNodeConnectionService extends ASectionService {

    private $validator;
    private $testRepository;
    private $testNodeRepository;
    private $testNodePortRepository;

    public function __construct(TestNodeConnectionRepository $repository, RecursiveValidator $validator, TestRepository $testRepository, TestNodeRepository $testNodeRepository, TestNodePortRepository $testNodePortRepository, AuthorizationChecker $securityAuthorizationChecker) {
        parent::__construct($repository, $securityAuthorizationChecker);

        $this->validator = $validator;
        $this->testRepository = $testRepository;
        $this->testNodeRepository = $testNodeRepository;
        $this->testNodePortRepository = $testNodePortRepository;
    }

    public function get($object_id, $createNew = false, $secure = true) {
        $object = parent::get($object_id, $createNew, $secure);
        if ($createNew && $object === null) {
            $object = new TestNodeConnection();
        }
        return $object;
    }

    public function getByFlowTest($test_id) {
        return $this->authorizeCollection($this->repository->findByFlowTest($test_id));
    }

    public function save(User $user, $object_id, Test $flowTest, TestNode $sourceNode, $sourcePort, TestNode $destinationNode, $destinationPort, $returnFunction, $automatic) {
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
        $object->setReturnFunction($returnFunction ? $returnFunction : $sourcePort->getVariable()->getName());
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

    public function onObjectSaved(User $user, $is_new, TestNodeConnection $object) {
        if ($is_new) {
            if ($object->getSourcePort()->getVariable()->getType() == 2) {
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
                                $this->save($user, 0, $object->getFlowTest(), $srcNode, $srcPort, $dstNode, $dstPort, $srcPort->getVariable()->getName(), true);
                                break;
                            }
                        }
                    }
                }
            }
        }
    }

    public function delete($object_ids, $secure = true) {
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

    public function onObjectDeleted(TestNodeConnection $object) {
        $this->repository->deleteAutomatic($object->getSourceNode(), $object->getDestinationNode());
    }

    public function entityToArray(TestWizardStep $ent) {
        $e = $ent->jsonSerialize();
        return $e;
    }

    public function importFromArray(User $user, $newName, $obj, &$map, &$queue) {
        $flowTest = null;
        if (array_key_exists("Test", $map)) {
            $flowTest_id = $map["Test"]["id" . $obj["flowTest"]];
            $flowTest = $this->testRepository->find($flowTest_id);
        }

        $sourceNode = null;
        if (array_key_exists("TestNode", $map)) {
            $sourceNode_id = $map["TestNode"]["id" . $obj["sourceNode"]];
            $sourceNode = $this->testNodeRepository->find($sourceNode_id);
        }

        $destinationNode = null;
        if (array_key_exists("TestNode", $map)) {
            $destinationNode_id = $map["TestNode"]["id" . $obj["destinationNode"]];
            $destinationNode = $this->testNodeRepository->find($destinationNode_id);
        }

        $sourcePort = null;
        if (array_key_exists("TestNodePort", $map) && $obj["sourcePort"]) {
            $sourcePort_id = $map["TestNodePort"]["id" . $obj["sourcePort"]];
            $sourcePort = $this->testNodePortRepository->find($sourcePort_id);
        }

        $destinationPort = null;
        if (array_key_exists("TestNodePort", $map) && $obj["destinationPort"]) {
            $destinationPort_id = $map["TestNodePort"]["id" . $obj["destinationPort"]];
            $destinationPort = $this->testNodePortRepository->find($destinationPort_id);
        }

        $ent = new TestNodeConnection();
        $ent->setDestinationNode($destinationNode);
        $ent->setDestinationPort($destinationPort);
        $ent->setFlowTest($flowTest);
        $ent->setReturnFunction($obj["returnFunction"]);
        $ent->setSourceNode($sourceNode);
        $ent->setSourcePort($sourcePort);
        $ent->setAutomatic($obj["automatic"] == "1");
        $ent_errors = $this->validator->validate($ent);
        $ent_errors_msg = array();
        foreach ($ent_errors as $err) {
            array_push($ent_errors_msg, $err->getMessage());
        }
        if (count($ent_errors_msg) > 0) {
            return array("errors" => $ent_errors_msg, "entity" => null, "source" => $obj);
        }
        $this->repository->save($ent);

        if (!array_key_exists("TestNodeConnection", $map)) {
            $map["TestNodeConnection"] = array();
        }
        $map["TestNodeConnection"]["id" . $obj["id"]] = $ent->getId();

        return array("errors" => null, "entity" => $ent);
    }

}
