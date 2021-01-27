<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Repository\TestNodeRepository;
use Concerto\PanelBundle\Entity\User;
use Concerto\PanelBundle\Entity\TestNode;
use Concerto\PanelBundle\Entity\Test;
use Concerto\PanelBundle\Repository\TestRepository;
use Concerto\PanelBundle\Security\ObjectVoter;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TestNodeService extends ASectionService
{

    const TYPE_REGULAR = 0;
    const TYPE_BEGIN_TEST = 1;
    const TYPE_FINISH_TEST = 2;

    private $validator;
    private $testNodePortService;
    private $testVariableService;
    private $testRepository;

    public function __construct(
        TestNodeRepository $repository,
        ValidatorInterface $validator,
        TestNodePortService $portService,
        TestVariableService $variableService,
        TestRepository $testRepository,
        AuthorizationCheckerInterface $securityAuthorizationChecker,
        TokenStorageInterface $securityTokenStorage,
        AdministrationService $administrationService,
        LoggerInterface $logger)
    {
        parent::__construct($repository, $securityAuthorizationChecker, $securityTokenStorage, $administrationService, $logger);

        $this->testNodePortService = $portService;
        $this->testVariableService = $variableService;
        $this->validator = $validator;
        $this->testRepository = $testRepository;
    }

    public function get($object_id, $createNew = false, $secure = true)
    {
        $object = parent::get($object_id, $createNew, $secure);
        if ($createNew && $object === null) {
            $object = new TestNode();
        }
        return $object;
    }

    public function getByFlowTest($test_id)
    {
        return $this->authorizeCollection($this->repository->findByFlowTest($test_id));
    }

    public function save($object_id, $type, $posX, $posY, Test $flowTest, Test $sourceTest, $title, $flush = true)
    {
        $errors = array();
        $object = $this->get($object_id);
        if ($object === null) {
            $object = new TestNode();
        }
        $object->setType($type);
        $object->setPosX($posX);
        $object->setPosY($posY);
        $object->setFlowTest($flowTest);
        $object->setSourceTest($sourceTest);
        $object->setTitle($title);

        foreach ($this->validator->validate($object) as $err) {
            array_push($errors, $err->getMessage());
        }
        if (count($errors) > 0) {
            return array("object" => null, "errors" => $errors);
        }
        $this->update($object, $flush);

        return array("object" => $object, "errors" => $errors);
    }

    public function update(TestNode $object, $flush = true)
    {
        $isNew = $object->getId() === null;
        $changeSet = $this->repository->getChangeSet($object);
        if ($isNew || !empty($changeSet)) {
            $this->repository->save($object, $flush);
            $this->onObjectSaved($object, $flush);
        }
    }

    private function onObjectSaved(TestNode $object, $flush = true)
    {
        $this->savePorts($object, $flush);
    }

    private function savePorts(TestNode $node, $flush = true)
    {
        switch ($node->getType()) {
            case self::TYPE_BEGIN_TEST:
                $params = array();
                $returns = $this->testVariableService->getParameters($node->getSourceTest()->getId());
                $outs = array();
                break;
            case self::TYPE_FINISH_TEST:
                $params = $this->testVariableService->getReturns($node->getSourceTest()->getId());
                $returns = array();
                $outs = array();
                break;
            default:
                $params = $this->testVariableService->getParameters($node->getSourceTest()->getId());
                $returns = $this->testVariableService->getReturns($node->getSourceTest()->getId());
                $outs = $this->testVariableService->getBranches($node->getSourceTest()->getId());
                break;
        }

        $vars = array($params, $returns, $outs);

        foreach ($vars as $collection) {
            foreach ($collection as $var) {
                $port = $this->testNodePortService->getOneByNodeAndVariable($node, $var);
                if (!$port) {
                    $exposed = $var->getType() == 2;
                    $result = $this->testNodePortService->save(0, $node, $var, "1", $var->getValue(), "1", null, false, $exposed, null, null, null, $flush);
                }
            }
        }
    }

    public function delete($object_ids, $secure = true, $flush = true)
    {
        $object_ids = explode(",", $object_ids);

        $result = array();
        foreach ($object_ids as $object_id) {
            $object = $this->get($object_id, false, $secure);
            if ($object) {
                $this->repository->delete($object, $flush);
                array_push($result, array("object" => $object, "errors" => array()));
            }
        }
        return $result;
    }

    public function importFromArray($instructions, $obj, &$map, &$renames, &$queue)
    {
        $pre_queue = array();
        if (!isset($map["TestNode"]))
            $map["TestNode"] = array();
        if (isset($map["TestNode"]["id" . $obj["id"]])) {
            return array("errors" => null, "entity" => $map["TestNode"]["id" . $obj["id"]]);
        }

        $flowTest = null;
        if (isset($map["Test"]) && isset($map["Test"]["id" . $obj["flowTest"]])) {
            $flowTest = $map["Test"]["id" . $obj["flowTest"]];
        }

        $sourceTest = null;
        if (isset($map["Test"]) && isset($map["Test"]["id" . $obj["sourceTest"]])) {
            $sourceTest = $map["Test"]["id" . $obj["sourceTest"]];
        }
        if (!$sourceTest) {
            foreach ($queue as $elem) {
                if ($elem["class_name"] == "Test" && $elem["id"] == $obj["sourceTest"]) {
                    array_push($pre_queue, $elem);
                    break;
                }
            }
        }

        if (count($pre_queue) > 0) {
            return array("pre_queue" => $pre_queue);
        }

        $parent_instruction = self::getObjectImportInstruction(array(
            "class_name" => "Test",
            "id" => $obj["flowTest"]
        ), $instructions);

        //node should never be converted
        if ($parent_instruction["action"] == 0 || $parent_instruction["action"] == 1) { //new or convert
            $result = $this->importNew(null, $obj, $map, $queue, $flowTest, $sourceTest);

            array_splice($queue, 1, 0, $obj["ports"]);
            return $result;
        }
        return null;
    }

    protected function importNew($new_name, $obj, &$map, &$queue, $flowTest, $sourceTest)
    {
        $user = null;
        $token = $this->securityTokenStorage->getToken();
        if ($token !== null) $user = $token->getUser();

        $ent = new TestNode();
        $ent->setFlowTest($flowTest);
        $ent->setPosX($obj["posX"]);
        $ent->setPosY($obj["posY"]);
        $ent->setSourceTest($sourceTest);
        $ent->setType($obj["type"]);
        if (isset($obj["title"]))
            $ent->setTitle($obj["title"]);
        $ent_errors = $this->validator->validate($ent);
        $ent_errors_msg = array();
        foreach ($ent_errors as $err) {
            array_push($ent_errors_msg, $err->getMessage());
        }
        if (count($ent_errors_msg) > 0) {
            return array("errors" => $ent_errors_msg, "entity" => null, "source" => $obj);
        }
        //shouldn't be update because it will lead to redundant ports
        $this->repository->save($ent, false);
        $map["TestNode"]["id" . $obj["id"]] = $ent;
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

    public function exposePorts($ports)
    {
        $this->testNodePortService->exposePorts($ports);
    }

    public function addDynamicPort($object_id, $name, $type)
    {
        return $this->testNodePortService->addDynamic(
            $this->get($object_id),
            $name,
            $type
        );
    }
}
