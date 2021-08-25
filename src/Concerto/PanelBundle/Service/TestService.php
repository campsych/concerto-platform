<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Entity\Test;
use Concerto\PanelBundle\Entity\TestVariable;
use Concerto\PanelBundle\Entity\ViewTemplate;
use Concerto\PanelBundle\Entity\TestNodePort;
use Concerto\PanelBundle\Repository\TestRepository;
use Concerto\PanelBundle\Entity\User;
use Cocur\Slugify\Slugify;
use Concerto\PanelBundle\Repository\TestSessionRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
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
    private $testSessionRepository;

    public function __construct(
        TestRepository $repository,
        ValidatorInterface $validator,
        Slugify $slugifier,
        TestVariableService $testVariableService,
        TestNodeService $testNodeService,
        TestNodeConnectionService $testNodeConnectionService,
        TestNodePortService $testNodePortService,
        AuthorizationCheckerInterface $securityAuthorizationChecker,
        TestWizardParamService $testWizardParamService,
        TokenStorageInterface $securityTokenStorage,
        AdministrationService $administrationService,
        LoggerInterface $logger,
        TestSessionRepository $testSessionRepository)
    {
        parent::__construct($repository, $validator, $securityAuthorizationChecker, $securityTokenStorage, $administrationService, $logger);

        $this->testVariableService = $testVariableService;
        $this->testNodeService = $testNodeService;
        $this->testNodeConnectionService = $testNodeConnectionService;
        $this->testNodePortService = $testNodePortService;
        $this->slugifier = $slugifier;
        $this->testWizardParamService = $testWizardParamService;
        $this->testSessionRepository = $testSessionRepository;
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

    public function save($object_id, $name, $description, $accessibility, $archived, $owner, $groups, $visibility, $type, $code, $sourceWizard, $urlslug, $serializedVariables, $baseTemplate, $protected)
    {
        $user = null;
        $token = $this->securityTokenStorage->getToken();
        if ($token !== null) $user = $token->getUser();

        $errors = array();
        $object = $this->get($object_id);
        if ($object === null) {
            $object = new Test();
            $object->setOwner($user);
        }
        $object->setName($name);
        if ($description !== null) {
            $object->setDescription($description);
        }
        $object->setVisibility($visibility);
        $object->setProtected($protected);

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
        $object->setBaseTemplate($baseTemplate);

        $urlslug = (trim((string)$urlslug) !== '') ? $this->slugifier->slugify($urlslug) : sha1(rand(0, 9999999));

        $object->setSlug($urlslug);
        $slug_postfix = 2;

        while ($this->getBySlug($object->getSlug(), $object->getId(), false)) {
            $object->setSlug($urlslug . '-' . $slug_postfix++);
        }

        return $this->resave($object, $serializedVariables, $errors);
    }

    private function resave(Test $object, $serializedVariables = null, $errors = array(), $flush = true)
    {
        foreach ($this->validator->validate($object) as $err) {
            array_push($errors, $err->getMessage());
        }
        if (count($errors) > 0) {
            return array("object" => null, "errors" => $errors);
        }
        $this->update($object, $serializedVariables, $flush);
        return array("object" => $object, "errors" => $errors);
    }

    private function update(Test $object, $serializedVariables = null, $flush = true)
    {
        $user = null;
        $token = $this->securityTokenStorage->getToken();
        if ($token !== null) $user = $token->getUser();

        $object->setUpdatedBy($user);
        $isNew = $object->getId() === null;
        $changeSet = $this->repository->getChangeSet($object);
        if ($isNew || !empty($changeSet)) {
            $this->repository->save($object, $flush);
            $this->onObjectSaved($object, $isNew, $serializedVariables, $flush);
            $isRenamed = !$isNew && isset($changeSet["name"]);
            if ($isRenamed) $this->testWizardParamService->onObjectRename($object, $changeSet["name"][0]);
        }
    }

    private function onObjectSaved($test, $isNew, $serializedVariables, $flush = true)
    {
        if ($test->getSourceWizard() != null) {
            if ($isNew) {
                $this->testVariableService->createVariablesFromSourceTest($test, $flush);
            } else {
                $this->testVariableService->saveCollection($serializedVariables, $test, $flush);
            }
        }
        if ($isNew && count($this->testVariableService->getBranches($test->getId())) == 0) {
            $this->testVariableService->save(0, "out", 2, "", false, 0, $test, null, $flush);
        }
        $this->updateDependentTests($test, $flush);

        if ($test->getType() == Test::TYPE_FLOW && $isNew) {
            $this->testNodeService->save(0, 1, 15000, 15000, $test, $test, "", $flush);
            $this->testNodeService->save(0, 2, 15500, 15100, $test, $test, "", $flush);
        }
    }

    public function updateDependentTests(Test $sourceTest, $flush = true)
    {
        $tests = $sourceTest->getDependantTests();

        $result = array();
        foreach ($tests as $test) {
            $data = $this->resave($test, null, array(), $flush);
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

            if (count($object->getWizards()) > 0) {
                array_push($result, array("object" => $object, "errors" => array("validate.test.delete.referenced")));
                continue;
            }

            $this->repository->delete($object);
            array_push($result, array("object" => $object, "errors" => array()));
        }
        return $result;
    }

    public function convertToExportable($array, $instruction = null, $secure = true)
    {
        $array = parent::convertToExportable($array, $instruction, $secure);
        unset($array["slug"]);
        unset($array["steps"]);
        unset($array["sourceWizardName"]);
        unset($array["sourceWizardTestName"]);
        return $array;
    }

    public function importFromArray($instructions, $obj, &$map, &$renames, &$queue)
    {
        $pre_queue = array();
        if (!isset($renames["Test"]))
            $renames["Test"] = array();
        if (!isset($map["Test"]))
            $map["Test"] = array();
        if (isset($map["Test"]["id" . $obj["id"]])) {
            return array("errors" => null, "entity" => $map["Test"]["id" . $obj["id"]]);
        }

        $wizard = null;
        if ($obj["sourceWizard"]) {
            if (isset($map["TestWizard"]) && isset($map["TestWizard"]["id" . $obj["sourceWizard"]])) {
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

        $baseTemplate = null;
        if (isset($obj["baseTemplate"]) && $obj["baseTemplate"]) {
            if (isset($map["ViewTemplate"]) && isset($map["ViewTemplate"]["id" . $obj["baseTemplate"]])) {
                $baseTemplate = $map["ViewTemplate"]["id" . $obj["baseTemplate"]];
            }
            if (!$baseTemplate) {
                foreach ($queue as $elem) {
                    if ($elem["class_name"] == "ViewTemplate" && $elem["id"] == $obj["baseTemplate"]) {
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
        $new_name = $this->getNextValidName($this->formatImportName($instruction["rename"], $obj), $instruction["action"], $old_name);
        if ($instruction["action"] != 2 && $old_name != $new_name) {
            $renames["Test"][$old_name] = $new_name;
        }

        $result = array();
        $src_ent = $this->findConversionSource($obj, $map);
        if ($instruction["action"] == 1 && $src_ent) {
            $result = $this->importConvert($new_name, $src_ent, $obj, $map, $queue, $wizard, $baseTemplate);
            if (isset($instruction["clean"]) && $instruction["clean"] == 1) $this->cleanConvert($result["entity"], $obj);
        } else if ($instruction["action"] == 2 && $src_ent) {
            $map["Test"]["id" . $obj["id"]] = $src_ent;
            $result = array("errors" => null, "entity" => $src_ent);
        } else
            $result = $this->importNew($new_name, $obj, $map, $queue, $wizard, $baseTemplate);

        if ($instruction["action"] != 2) {
            array_splice($queue, 1, 0, $obj["nodesConnections"]);
            array_splice($queue, 1, 0, $obj["nodes"]);
        }
        array_splice($queue, 1, 0, $obj["variables"]);

        return $result;
    }

    private function cleanConvert(Test $entity, $importArray)
    {
        foreach ($entity->getVariables() as $currentVariable) {
            /** @var TestVariable $currentVariable */
            $found = false;
            foreach ($importArray["variables"] as $importVariable) {
                if ($currentVariable->getName() == $importVariable["name"] && $currentVariable->getType() == $importVariable["type"]) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $this->repository->delete($currentVariable);
            }
        }
    }

    protected function importNew($new_name, $obj, &$map, &$queue, $wizard, $baseTemplate)
    {
        $starter_content = $obj["name"] == $new_name ? $obj["starterContent"] : false;

        $user = null;
        $token = $this->securityTokenStorage->getToken();
        if ($token !== null) $user = $token->getUser();

        $ent = new Test();
        $ent->setName($new_name);
        $ent->setDescription($obj["description"]);
        $ent->setVisibility($obj["visibility"]);
        $ent->setType($obj["type"]);
        $ent->setCode($obj["code"]);
        $ent->setSourceWizard($wizard);
        $ent->setTags($obj["tags"]);
        $ent->setOwner($user);
        $ent->setUpdatedBy($user);
        $ent->setStarterContent($starter_content);
        $ent->setAccessibility($obj["accessibility"]);
        $ent->setBaseTemplate($baseTemplate);
        if (array_key_exists("protected", $obj)) $ent->setProtected($obj["protected"]);
        $ent_errors = $this->validator->validate($ent);
        $ent_errors_msg = array();
        foreach ($ent_errors as $err) {
            array_push($ent_errors_msg, $err->getMessage());
        }
        if (count($ent_errors_msg) > 0) {
            return array("errors" => $ent_errors_msg, "entity" => null, "source" => $obj);
        }
        //shouldn't be update because it will lead to redundant variables
        $this->repository->save($ent);
        $map["Test"]["id" . $obj["id"]] = $ent;
        return array("errors" => null, "entity" => $ent);
    }

    protected function findConversionSource($obj, $map)
    {
        return $this->get($obj["name"]);
    }

    protected function importConvert($new_name, $src_ent, $obj, &$map, &$queue, $wizard, $baseTemplate)
    {
        $user = null;
        $token = $this->securityTokenStorage->getToken();
        if ($token !== null) $user = $token->getUser();

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
        $ent->setBaseTemplate($baseTemplate);
        if (array_key_exists("protected", $obj)) $ent->setProtected($obj["protected"]);
        $ent_errors = $this->validator->validate($ent);
        $ent_errors_msg = array();
        foreach ($ent_errors as $err) {
            array_push($ent_errors_msg, $err->getMessage());
        }
        if (count($ent_errors_msg) > 0) {
            return array("errors" => $ent_errors_msg, "entity" => null, "source" => $obj);
        }
        $this->update($ent, null);
        $map["Test"]["id" . $obj["id"]] = $ent;

        $this->onConverted($ent, $old_ent);

        return array("errors" => null, "entity" => $ent);
    }

    protected function onConverted($new_ent, $old_ent)
    {
        $this->removeAllNodes($new_ent);
    }

    private function removeAllNodes(Test $test)
    {
        $this->repository->removeAllNodes($test);
        $this->repository->save($test);
    }

    public function addFlowNode($type, $posX, $posY, Test $flowTest, Test $sourceTest, $title, $return_collections = false)
    {
        $result = $this->testNodeService->save(0, $type, $posX, $posY, $flowTest, $sourceTest, $title);
        if ($return_collections) {
            $result["collections"] = $this->getFlowCollections($flowTest->getId());
        }
        return $result;
    }

    public function moveFlowNode($nodes)
    {
        for ($i = 0; $i < count($nodes); $i++) {
            $node = $this->testNodeService->get($nodes[$i]["id"]);
            $node->setPosX($nodes[$i]["posX"]);
            $node->setPosY($nodes[$i]["posY"]);
            $this->testNodeService->update($node);
        }
    }

    public function addFlowConnection(Test $flowTest, $sourceNode, $sourcePort, $destinationNode, $destinationPort, $returnFunction, $default, $return_collections = false)
    {
        $sourceNode = $this->testNodeService->get($sourceNode);
        $sourcePort = $this->testNodePortService->get($sourcePort);
        $destinationNode = $this->testNodeService->get($destinationNode);
        $destinationPort = $this->testNodePortService->get($destinationPort);
        $result = $this->testNodeConnectionService->save(0, $flowTest, $sourceNode, $sourcePort, $destinationNode, $destinationPort, $returnFunction, $default);
        if ($return_collections) {
            $result["collections"] = $this->getFlowCollections($flowTest->getId());
        } else {
            $result["collections"] = array();
            $result["collections"]["newNodesConnections"] = $this->testNodeConnectionService->repository->findByNodes($sourceNode, $destinationNode);
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

    public function pasteNodes(Test $flowTest, $nodes, $return_collections = false)
    {
        /** @var TestNodePort $newPort */

        $node_map = array();
        $result = array(
            "errors" => array(),
            "collections" => array("newNodes" => array(), "newNodesConnections" => array())
        );
        foreach ($nodes as $node) {
            $node_result = $this->addFlowNode($node["type"], $node["posX"], $node["posY"], $flowTest, $this->get($node["sourceTest"]), $node["title"], false);
            $newNode = $node_result["object"];
            array_push($result["collections"]["newNodes"], $newNode);
            $node_map["id" . $node["id"]] = $newNode->getId();

            foreach ($node["ports"] as $srcPort) {
                $found = false;
                foreach ($newNode->getPorts() as $newPort) {
                    if ($srcPort["variable"] !== null && $srcPort["variable"] == $newPort->getVariable()->getId()) {
                        $newPort->setDefaultValue($srcPort["defaultValue"]);
                        $newPort->setValue($srcPort["value"]);
                        $newPort->setString($srcPort["string"]);
                        $newPort->setExposed($srcPort["exposed"]);
                        $newPort->setPointer($srcPort["pointer"]);
                        $newPort->setPointerVariable($srcPort["pointerVariable"]);
                        $this->testNodePortService->update($newPort);
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $this->testNodePortService->save(
                        0,
                        $newNode,
                        $srcPort["variable"],
                        $srcPort["defaultValue"],
                        $srcPort["value"],
                        $srcPort["string"],
                        $srcPort["type"],
                        $srcPort["dynamic"],
                        $srcPort["exposed"],
                        $srcPort["name"],
                        $srcPort["pointer"],
                        $srcPort["pointerVariable"]
                    );
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

            $connection_result = $this->addFlowConnection($flowTest, $source_node, $source_port, $destination_node, $destination_port, $copied_connection->getReturnFunction(), $copied_connection->hasDefaultReturnFunction(), false);
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

    public function getBaseTemplateContent($test_slug = null, $test_name = null, $existing_session_hash = null)
    {
        /** @var Test $test */
        /** @var ViewTemplate $template */

        $test = null;
        if ($existing_session_hash !== null) {
            $session = $this->testSessionRepository->findOneBy(array("hash" => $existing_session_hash));
            if ($session) {
                $test = $session->getTest();
            }
        } else {
            if ($test_name !== null) {
                $test = $this->repository->findRunnableByName($test_name);
            } else {
                $test = $this->repository->findRunnableBySlug($test_slug);
            }
        }
        if (!$test) return null;

        $template = $test->getBaseTemplate();
        if (!$template) return null;

        return $template->getHtml();
    }
}
