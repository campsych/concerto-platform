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
use Psr\Log\LoggerInterface;
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
        TestNodeConnectionRepository $testNodeConnectionRepository,
        AdministrationService $administrationService,
        LoggerInterface $logger)
    {
        parent::__construct($repository, $securityAuthorizationChecker, $securityTokenStorage, $administrationService, $logger);

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

    public function save($object_id, Test $flowTest, TestNode $sourceNode, $sourcePort, TestNode $destinationNode, $destinationPort, $returnFunction, $default)
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

        foreach ($this->validator->validate($object) as $err) {
            array_push($errors, $err->getMessage());
        }
        if (count($errors) > 0) {
            return array("object" => null, "errors" => $errors);
        }
        $this->update($object);

        return array("object" => $object, "errors" => $errors);
    }

    private function update(TestNodeConnection $object, $flush = true)
    {
        $this->repository->save($object, $flush);
    }

    public function updateDefaultReturnFunctions(TestNodePort $sourcePort)
    {
        $connections = $sourcePort->getSourceForConnectionsByDefaultReturnFunction(true);

        foreach ($connections as $connection) {
            $connection->setReturnFunction($sourcePort->getName());
            $this->update($connection);
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
    }

    public function onTestVariableSaved(TestVariable $variable, $is_new, $flush = true)
    {
        $connections = $variable->getTest()->getNodesConnectionBySourcePortVariable($variable);

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
        if (!isset($map["TestNodeConnection"]))
            $map["TestNodeConnection"] = array();
        if (isset($map["TestNodeConnection"]["id" . $obj["id"]]))
            return array("errors" => null, "entity" => $map["TestNodeConnection"]["id" . $obj["id"]]);

        $flowTest = null;
        if (isset($map["Test"]) && isset($map["Test"]["id" . $obj["flowTest"]])) {
            $flowTest = $map["Test"]["id" . $obj["flowTest"]];
        }

        $sourceNode = null;
        if (isset($map["TestNode"]) && isset($map["TestNode"]["id" . $obj["sourceNode"]])) {
            $sourceNode = $map["TestNode"]["id" . $obj["sourceNode"]];
        }

        $destinationNode = null;
        if (isset($map["TestNode"]) && isset($map["TestNode"]["id" . $obj["destinationNode"]])) {
            $destinationNode = $map["TestNode"]["id" . $obj["destinationNode"]];
        }

        $sourcePort = null;
        if ($obj["sourcePort"]) {
            if (isset($map["TestNodePort"]) && isset($map["TestNodePort"]["id" . $obj["sourcePort"]])) {
                $sourcePort = $map["TestNodePort"]["id" . $obj["sourcePort"]];
            }
        }

        $destinationPort = null;
        if ($obj["destinationPort"]) {
            if (isset($map["TestNodePort"]) && isset($map["TestNodePort"]["id" . $obj["destinationPort"]])) {
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

        //connection should never be converted
        if ($parent_instruction["action"] == 0 || $parent_instruction["action"] == 1) { //new or convert
            return $this->importNew(null, $obj, $map, $queue, $destinationNode, $destinationPort, $flowTest, $sourcePort, $sourceNode);
        }
        return null;
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
        if (isset($obj["defaultReturnFunction"]))
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
