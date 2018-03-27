<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Entity\Test;
use Concerto\PanelBundle\Repository\TestRepository;
use Concerto\PanelBundle\Entity\User;
use Cocur\Slugify\Slugify;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TestService extends AExportableSectionService
{

    private $testVariableService;
    private $testNodeService;
    private $testNodeConnectionService;
    private $testNodePortService;
    private $slugifier;
    private $testWizardParamService;

    public function __construct(TestRepository $repository, ValidatorInterface $validator, Slugify $slugifier, TestVariableService $testVariableService, TestNodeService $testNodeService, TestNodeConnectionService $testNodeConnectionService, TestNodePortService $testNodePortService, AuthorizationCheckerInterface $securityAuthorizationChecker, TestWizardParamService $testWizardParamService)
    {
        parent::__construct($repository, $validator, $securityAuthorizationChecker);

        $this->testVariableService = $testVariableService;
        $this->testNodeService = $testNodeService;
        $this->testNodeConnectionService = $testNodeConnectionService;
        $this->testNodePortService = $testNodePortService;
        $this->slugifier = $slugifier;
        $this->testWizardParamService = $testWizardParamService;
    }

    public function get($object_id, $createNew = false, $secure = true)
    {
        $object = null;
        if (is_numeric($object_id)) {
            $object = parent::get($object_id, $createNew, $secure);
        } else {
            $object = $this->repository->findOneByName($object_id);
            if ($secure) {
                $object = $this->authorizeObject($object);
            }
        }
        if ($createNew && $object === null) {
            $object = new Test();
        }
        return $object;
    }

    public function getBySlug($object_slug, $ignored_id = false, $authorize = true)
    {
        $object = $this->repository->findOneBySlug($object_slug);
        if ($authorize)
            $object = $this->authorizeObject($object);
        if (!empty($object) && $ignored_id && $ignored_id === $object->getId())
            $object = null;

        return $object;
    }

    public function save(User $user, $object_id, $name, $description, $accessibility, $archived, $owner, $groups, $visibility, $type, $code, $sourceWizard, $urlslug, $serializedVariables)
    {
        $errors = array();
        $object = $this->get($object_id);
        $new = false;
        $old_name = null;
        if ($object === null) {
            $object = new Test();
            $new = true;
            $object->setOwner($user);
        } else {
            $old_name = $object->getName();
        }
        $object->setName($name);
        if ($description !== null) {
            $object->setDescription($description);
        }
        $object->setVisibility($visibility);

        if (!self::$securityOn || $this->securityAuthorizationChecker->isGranted(User::ROLE_SUPER_ADMIN)) {
            $object->setAccessibility($accessibility);
            $object->setOwner($owner);
            $object->setGroups($groups);
        }

        $object->setArchived($archived);
        $object->setType($type);
        if ($code !== null) {
            $object->setCode($code);
        }
        $object->setSourceWizard($sourceWizard);

        $urlslug = (trim((string)$urlslug) !== '') ? $this->slugifier->slugify($urlslug) : sha1(rand(0, 9999999));

        $object->setSlug($urlslug);
        $slug_postfix = 2;

// assuring that the slug is unique - with random one it's a bit unlikely, but with user input it's possible
        while ($this->getBySlug($object->getSlug(), $object->getId(), false)) {
            $object->setSlug($urlslug . '-' . $slug_postfix++);
        }

        return $this->resave($new, $user, $object, $old_name, $serializedVariables, $errors);
    }

    private function resave($new, User $user, Test $object, $old_name, $serializedVariables = null, $errors = array(), $flush = true)
    {
        $object->setUpdated();
        if ($user !== null)
            $object->setUpdatedBy($user->getUsername());
        if ($object->getSourceWizard() != null) {
            $object->setCode($object->getSourceWizard()->getTest()->getCode());
        }
        $object->setOutdated(false);

        foreach ($this->validator->validate($object) as $err) {
            array_push($errors, $err->getMessage());
        }
        if (count($errors) > 0) {
            return array("object" => null, "errors" => $errors);
        }

        $this->repository->save($object, $flush);
        $this->onObjectSaved($object, $old_name, $new, $user, $serializedVariables, true, $flush);

        return array("object" => $object, "errors" => $errors);
    }

    private function onObjectSaved($test, $oldName, $new, User $user, $serializedVariables, $insertInitialObjects = true, $flush = true)
    {
        if ($test->getSourceWizard() != null) {
            if ($new) {
                $this->testVariableService->createVariablesFromSourceTest($user, $test, $flush);
            } else {
                $this->testVariableService->saveCollection($user, $serializedVariables, $test, $flush);
            }
        }
        if ($new && count($this->testVariableService->getBranches($test->getId())) == 0 && $insertInitialObjects) {
            $result = $this->testVariableService->save($user, 0, "out", 2, "", false, 0, $test, null, $flush);
            $test->addVariable($result["object"]);
        }
        $this->updateDependentTests($user, $test, $flush);

        if ($test->getType() == Test::TYPE_FLOW && $new && $insertInitialObjects) {
            $result = $this->testNodeService->save($user, 0, 1, 15000, 15000, $test, $test, "", $flush);
            $test->addNode($result["object"]);
            $result = $this->testNodeService->save($user, 0, 2, 15500, 15100, $test, $test, "", $flush);
            $test->addNode($result["object"]);
        }

        if (!$new && $oldName != $test->getName()) {
            $this->testWizardParamService->onObjectRename($user, $test, $oldName);
        }
    }

    public function markDependentTestsOutdated($object_id)
    {
        $this->repository->markDependentTestsOutdated($object_id);
    }

    public function updateDependentTests(User $user, Test $sourceTest, $flush = true)
    {
        $tests = $this->repository->findDependent($sourceTest);

        $result = array();
        foreach ($tests as $test) {
            $data = $this->resave(false, $user, $test, null, null, array(), $flush);
            array_push($result, $data);
        }
        return $result;
    }

    public function delete($object_ids, $secure = true)
    {
        $object_ids = explode(",", $object_ids);

        $result = array();
        foreach ($object_ids as $object_id) {
            $object = $this->get($object_id, false, $secure);
            if ($object === null)
                continue;

            if ($object->getWizards()->count() > 0) {
                array_push($result, array("object" => $object, "errors" => array("validate.test.delete.referenced")));
                continue;
            }

            $this->repository->delete($object);
            array_push($result, array("object" => $object, "errors" => array()));
        }
        return $result;
    }

    public function convertToExportable($array)
    {
        unset($array["logs"]);
        unset($array["slug"]);
        unset($array["steps"]);
        return $array;
    }

    public function importFromArray(User $user, $instructions, $obj, &$map, &$queue)
    {
        $pre_queue = array();
        if (!array_key_exists("Test", $map))
            $map["Test"] = array();
        if (array_key_exists("id" . $obj["id"], $map["Test"])) {
            return array("errors" => null, "entity" => $map["Test"]["id" . $obj["id"]]);
        }

        $wizard = null;
        if ($obj["sourceWizard"]) {
            if (array_key_exists("TestWizard", $map) && array_key_exists("id" . $obj["sourceWizard"], $map["TestWizard"])) {
                $wizard = $map["TestWizard"]["id" . $obj["sourceWizard"]];
            }
            if (!$wizard) {
                foreach ($queue as $elem) {
                    if ($elem["class_name"] == "TestWizard" && $elem["id"] == $obj["sourceWizard"]) {
                        array_push($pre_queue, $elem);
                        break;
                    }
                }
            }
        }

        if (count($pre_queue) > 0) {
            return array("pre_queue" => $pre_queue);
        }

        $instruction = self::getObjectImportInstruction($obj, $instructions);
        $old_name = $instruction["existing_object_name"];
        $new_name = $this->getNextValidName($this->formatImportName($user, $instruction["rename"], $obj), $instruction["action"], $old_name);

        $result = array();
        $src_ent = $this->findConversionSource($obj, $map);
        if ($instruction["action"] == 1 && $src_ent) {
            $result = $this->importConvert($user, $new_name, $src_ent, $obj, $map, $queue, $wizard);
        } else if ($instruction["action"] == 2 && $src_ent) {
            $map["Test"]["id" . $obj["id"]] = $src_ent;
            $result = array("errors" => null, "entity" => $src_ent);
        } else
            $result = $this->importNew($user, $new_name, $obj, $map, $queue, $wizard);

        array_splice($queue, 1, 0, $obj["nodesConnections"]);
        array_splice($queue, 1, 0, $obj["nodes"]);
        array_splice($queue, 1, 0, $obj["variables"]);

        return $result;
    }

    protected function importNew(User $user, $new_name, $obj, &$map, &$queue, $wizard)
    {
        $starter_content = $obj["name"] == $new_name ? $obj["starterContent"] : false;

        $ent = new Test();
        $ent->setName($new_name);
        $ent->setDescription($obj["description"]);
        $ent->setVisibility($obj["visibility"]);
        $ent->setType($obj["type"]);
        $ent->setCode($obj["code"]);
        $ent->setSourceWizard($wizard);
        $ent->setTags($obj["tags"]);
        $ent->setOwner($user);
        $ent->setStarterContent($starter_content);
        $ent->setAccessibility($obj["accessibility"]);
        $ent_errors = $this->validator->validate($ent);
        $ent_errors_msg = array();
        foreach ($ent_errors as $err) {
            array_push($ent_errors_msg, $err->getMessage());
        }
        if (count($ent_errors_msg) > 0) {
            return array("errors" => $ent_errors_msg, "entity" => null, "source" => $obj);
        }
        $this->repository->save($ent, false);
        $map["Test"]["id" . $obj["id"]] = $ent;
        return array("errors" => null, "entity" => $ent);
    }

    protected function findConversionSource($obj, $map)
    {
        return $this->get($obj["name"]);
    }

    protected function importConvert(User $user, $new_name, $src_ent, $obj, &$map, &$queue, $wizard)
    {
        $old_ent = clone $src_ent;
        $ent = $src_ent;
        $ent->setName($new_name);
        $ent->setDescription($obj["description"]);
        $ent->setVisibility($obj["visibility"]);
        $ent->setType($obj["type"]);
        $ent->setCode($obj["code"]);
        $ent->setSourceWizard($wizard);
        $ent->setTags($obj["tags"]);
        $ent->setOwner($user);
        $ent->setStarterContent($obj["starterContent"]);
        $ent->setAccessibility($obj["accessibility"]);
        $ent_errors = $this->validator->validate($ent);
        $ent_errors_msg = array();
        foreach ($ent_errors as $err) {
            array_push($ent_errors_msg, $err->getMessage());
        }
        if (count($ent_errors_msg) > 0) {
            return array("errors" => $ent_errors_msg, "entity" => null, "source" => $obj);
        }
        $this->repository->save($ent, false);
        $map["Test"]["id" . $obj["id"]] = $ent;

        $this->updateDependentTests($user, $ent, false);
        $this->onConverted($ent, $old_ent);

        return array("errors" => null, "entity" => $ent);
    }

    protected function onConverted($new_ent, $old_ent)
    {
        if ($old_ent->getNodes()->count() > 0)
            $this->testNodeService->repository->deleteByTest($old_ent);
    }

    public function addFlowNode(User $user, $type, $posX, $posY, Test $flowTest, Test $sourceTest, $title, $return_collections = false)
    {
        $result = $this->testNodeService->save($user, 0, $type, $posX, $posY, $flowTest, $sourceTest, $title);
        if ($return_collections) {
            $result["collections"] = $this->getFlowCollections($flowTest->getId());
        }
        return $result;
    }

    public function removeFlowNode($node_ids, $return_collections = false)
    {
        $ids = explode(",", $node_ids);
        $first_node = $this->testNodeService->get($ids[0]);
        $result = array(
            "results" => $this->testNodeService->delete($node_ids)
        );
        if ($return_collections) {
            $result["collections"] = $this->getFlowCollections($first_node->getFlowTest()->getId());
        }
        return $result;
    }

    public function moveFlowNode($nodes)
    {
        for ($i = 0; $i < count($nodes); $i++) {
            $node = $this->testNodeService->get($nodes[$i]["id"]);
            $node->setPosX($nodes[$i]["posX"]);
            $node->setPosY($nodes[$i]["posY"]);
            $this->testNodeService->repository->save($node);
        }
    }

    public function addFlowConnection(User $user, Test $flowTest, $sourceNode, $sourcePort, $destinationNode, $destinationPort, $returnFunction, $automatic, $default, $return_collections = false)
    {
        $sourceNode = $this->testNodeService->get($sourceNode);
        $sourcePort = $this->testNodePortService->get($sourcePort);
        $destinationNode = $this->testNodeService->get($destinationNode);
        $destinationPort = $this->testNodePortService->get($destinationPort);
        $result = $this->testNodeConnectionService->save($user, 0, $flowTest, $sourceNode, $sourcePort, $destinationNode, $destinationPort, $returnFunction, $automatic, $default);
        if ($return_collections) {
            $result["collections"] = $this->getFlowCollections($flowTest->getId());
        } else {
            $result["collections"] = array();
            $result["collections"]["newNodesConnections"] = $this->testNodeConnectionService->repository->findByNodes($sourceNode, $destinationNode);
        }
        return $result;
    }

    public function removeFlowConnection($connection_id, $return_collections = false)
    {
        $connection = $this->testNodeConnectionService->get($connection_id);
        $result = $this->testNodeConnectionService->delete($connection_id)[0];
        if ($return_collections) {
            $result["collections"] = $this->getFlowCollections($connection->getSourceNode()->getFlowTest()->getId());
        }
        return $result;
    }

    public function getFlowCollections($flow_id)
    {
        return array(
            "nodes" => $this->testNodeService->getByFlowTest($flow_id),
            "nodesConnections" => $this->testNodeConnectionService->getByFlowTest($flow_id)
        );
    }

    public function pasteNodes(User $user, Test $flowTest, $nodes, $return_collections = false)
    {
        $node_map = array();
        $result = array(
            "errors" => array(),
            "collections" => array("newNodes" => array(), "newNodesConnections" => array())
        );
        foreach ($nodes as $node) {
            $node_result = $this->addFlowNode($user, $node["type"], $node["posX"], $node["posY"], $flowTest, $this->get($node["sourceTest"]), $node["title"], false);
            $new_node = $node_result["object"];
            array_push($result["collections"]["newNodes"], $new_node);
            $node_map["id" . $node["id"]] = $new_node->getId();

            foreach ($node["ports"] as $src_port) {
                foreach ($new_node->getPorts() as $dest_port) {
                    if ($src_port["variable"] !== null && $src_port["variable"] == $dest_port->getVariable()->getId()) {
                        $dest_port->setValue($src_port["value"]);
                        $dest_port->setString($src_port["string"]);
                        $dest_port->setDefaultValue($src_port["defaultValue"]);
                        $this->testNodePortService->repository->save($dest_port);
                        break;
                    }
                }
            }
        }

        $connections = array();
        foreach ($flowTest->getNodesConnections() as $connection) {
            $sourceNodeId = $connection->getSourceNode()->getId();
            $destinationNodeId = $connection->getDestinationNode()->getId();
            $src_found = false;
            $dst_found = false;

            foreach ($nodes as $node) {
                if ($sourceNodeId == $node["id"])
                    $src_found = true;
                if ($destinationNodeId == $node["id"])
                    $dst_found = true;
                if ($src_found && $dst_found) {
                    array_push($connections, $connection);
                    break;
                }
            }
        }

        foreach ($connections as $copied_connection) {
            $source_node = $this->testNodeService->get($node_map["id" . $copied_connection->getSourceNode()->getId()]);
            $destination_node = $this->testNodeService->get($node_map["id" . $copied_connection->getDestinationNode()->getId()]);

            $source_port = null;
            if ($copied_connection->getSourcePort() != null) {
                foreach ($source_node->getPorts() as $port) {
                    if ($port->getVariable()->getId() == $copied_connection->getSourcePort()->getVariable()->getId()) {
                        $source_port = $port;
                        break;
                    }
                }
            }

            $destination_port = null;
            if ($copied_connection->getDestinationPort() != null) {
                foreach ($destination_node->getPorts() as $port) {
                    if ($port->getVariable()->getId() == $copied_connection->getDestinationPort()->getVariable()->getId()) {
                        $destination_port = $port;
                        break;
                    }
                }
            }

            $connection_result = $this->addFlowConnection($user, $flowTest, $source_node, $source_port, $destination_node, $destination_port, $copied_connection->getReturnFunction(), $copied_connection->isAutomatic(), $copied_connection->hasDefaultReturnFunction(), false);
            $new_connection = $connection_result["object"];
            array_push($result["collections"]["newNodesConnections"], $new_connection);
        }

        if ($return_collections) {
            $result["collections"] = $this->getFlowCollections($flowTest->getId());
        } else {
            foreach ($result["collections"]["newNodes"] as $node) {
                $this->repository->refresh($node);
            }
            foreach ($result["collections"]["newNodesConnections"] as $connection) {
                $this->repository->refresh($connection);
            }
        }
        return $result;
    }

}
