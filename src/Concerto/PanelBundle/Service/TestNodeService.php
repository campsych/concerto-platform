<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Repository\TestNodeRepository;
use Concerto\PanelBundle\Entity\User;
use Concerto\PanelBundle\Entity\TestNode;
use Concerto\PanelBundle\Entity\Test;
use Concerto\PanelBundle\Repository\TestRepository;
use Concerto\PanelBundle\Security\ObjectVoter;
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

    public function __construct(TestNodeRepository $repository, ValidatorInterface $validator, TestNodePortService $portService, TestVariableService $variableService, TestRepository $testRepository, AuthorizationCheckerInterface $securityAuthorizationChecker)
    {
        parent::__construct($repository, $securityAuthorizationChecker);

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

    public function save(User $user, $object_id, $type, $posX, $posY, Test $flowTest, Test $sourceTest, $title, $flush = true)
    {
        $errors = array();
        $object = $this->get($object_id);
        $is_new = false;
        if ($object === null) {
            $object = new TestNode();
            $is_new = true;
        }
        $object->setUpdated();
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
        $this->repository->save($object, $flush);

        $this->savePorts($user, $object, $type, $sourceTest, $flush);

        return array("object" => $object, "errors" => $errors);
    }

    public function savePorts(User $user, TestNode $node, $type, Test $sourceTest, $flush = true)
    {
        switch ($type) {
            case self::TYPE_BEGIN_TEST:
                $params = array();
                $returns = $this->testVariableService->getParameters($sourceTest->getId());
                $outs = array();
                break;
            case self::TYPE_FINISH_TEST:
                $params = $this->testVariableService->getReturns($sourceTest->getId());
                $returns = array();
                $outs = array();
                break;
            default:
                $params = $this->testVariableService->getParameters($sourceTest->getId());
                $returns = $this->testVariableService->getReturns($sourceTest->getId());
                $outs = $this->testVariableService->getBranches($sourceTest->getId());
                break;
        }

        $vars = array($params, $returns, $outs);

        foreach ($vars as $collection) {
            foreach ($collection as $var) {
                $value = $var->getValue();
                if ($value) {
                    $value = '"' . addslashes($var->getValue()) . '"';
                }
                $port = $this->testNodePortService->getOneByNodeAndVariable($node, $var);
                if (!$port) {
                    $result = $this->testNodePortService->save($user, 0, $node, $var, "1", $var->getValue(), "1", $flush);
                    $node->addPort($result["object"]);
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

    public function importFromArray(User $user, $instructions, $obj, &$map, &$queue)
    {
        $pre_queue = array();
        if (!array_key_exists("TestNode", $map))
            $map["TestNode"] = array();
        if (array_key_exists("id" . $obj["id"], $map["TestNode"])) {
            return array("errors" => null, "entity" => $map["TestNode"]["id" . $obj["id"]]);
        }

        $flowTest = null;
        if (array_key_exists("Test", $map) && array_key_exists("id" . $obj["flowTest"], $map["Test"])) {
            $flowTest = $map["Test"]["id" . $obj["flowTest"]];
        }

        $sourceTest = null;
        if (array_key_exists("Test", $map) && array_key_exists("id" . $obj["sourceTest"], $map["Test"])) {
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
        $result = array();
        $src_ent = $this->findConversionSource($obj, $map);
        if ($parent_instruction["action"] == 2 && $src_ent) {
            $map["TestNode"]["id" . $obj["id"]] = $src_ent;
            $result = array("errors" => null, "entity" => $src_ent);
        } else
            $result = $this->importNew($user, null, $obj, $map, $queue, $flowTest, $sourceTest);

        array_splice($queue, 1, 0, $obj["ports"]);

        return $result;
    }

    /* TODO: improve it, curretly it's not 100% reliable (for nodes with same position) */

    protected function findConversionSource($obj, $map)
    {
        $ent = $this->repository->findOneBy(array(
            "posX" => $obj["posX"],
            "posY" => $obj["posY"],
            "flowTest" => $map["Test"]["id" . $obj["flowTest"]]
        ));
        if (!$ent)
            return null;
        return $this->get($ent->getId());
    }

    protected function importNew(User $user, $new_name, $obj, &$map, &$queue, $flowTest, $sourceTest)
    {
        $ent = new TestNode();
        $ent->setFlowTest($flowTest);
        $ent->setPosX($obj["posX"]);
        $ent->setPosY($obj["posY"]);
        $ent->setSourceTest($sourceTest);
        $ent->setType($obj["type"]);
        if (array_key_exists("title", $obj))
            $ent->setTitle($obj["title"]);
        $ent_errors = $this->validator->validate($ent);
        $ent_errors_msg = array();
        foreach ($ent_errors as $err) {
            array_push($ent_errors_msg, $err->getMessage());
        }
        if (count($ent_errors_msg) > 0) {
            return array("errors" => $ent_errors_msg, "entity" => null, "source" => $obj);
        }
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

}
