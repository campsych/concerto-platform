<?php

namespace Concerto\PanelBundle\Service;

use Symfony\Component\Validator\Validator\RecursiveValidator;
use Concerto\PanelBundle\Entity\Test;
use Concerto\PanelBundle\Repository\TestRepository;
use Concerto\PanelBundle\Entity\User;
use Symfony\Component\Security\Core\Util\SecureRandomInterface;
use Cocur\Slugify\Slugify;
use Concerto\PanelBundle\Entity\AEntity;
use Concerto\PanelBundle\Repository\TestWizardRepository;
use Concerto\PanelBundle\Service\TestNodeService;
use Concerto\PanelBundle\Service\TestNodeConnectionService;
use Concerto\PanelBundle\Service\TestNodePortService;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class TestService extends AExportableSectionService {

    private $testVariableService;
    private $testNodeService;
    private $testNodeConnectionService;
    private $testNodePortService;
    private $testWizardRepository;
    private $randomGenerator;
    private $slugifier;

    public function __construct(TestRepository $repository, RecursiveValidator $validator, SecureRandomInterface $randomGenerator, Slugify $slugifier, TestVariableService $testVariableService, TestWizardRepository $testWizardRepository, TestNodeService $testNodeService, TestNodeConnectionService $testNodeConnectionService, TestNodePortService $testNodePortService, AuthorizationChecker $securityAuthorizationChecker) {
        parent::__construct($repository, $validator, $securityAuthorizationChecker);

        $this->testVariableService = $testVariableService;
        $this->testNodeService = $testNodeService;
        $this->testNodeConnectionService = $testNodeConnectionService;
        $this->testNodePortService = $testNodePortService;
        $this->testWizardRepository = $testWizardRepository;
        $this->randomGenerator = $randomGenerator;
        $this->slugifier = $slugifier;
    }

    public function get($object_id, $createNew = false, $secure = true) {
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

    public function getBySlug($object_slug, $ignored_id = false, $authorize = true) {
        $object = $this->repository->findOneBySlug($object_slug);
        if ($authorize)
            $object = $this->authorizeObject($object);
        if (!empty($object) && $ignored_id && $ignored_id === $object->getId())
            $object = null;

        return $object;
    }

    public function save(User $user, $object_id, $name, $description, $accessibility, $protected, $archived, $owner, $groups, $visibility, $type, $code, $resumable, $sourceWizard, $urlslug, $serializedVariables) {
        $errors = array();
        $object = $this->get($object_id);
        $new = false;
        if ($object === null) {
            $object = new Test();
            $new = true;
            $object->setOwner($user);
        }
        $object->setName($name);
        if ($description !== null) {
            $object->setDescription($description);
        }
        $object->setVisibility($visibility);
        if (!$new && $object->isProtected() == $protected && $protected) {
            array_push($errors, "validate.protected.mod");
        }

        if (!self::$securityOn || $this->securityAuthorizationChecker->isGranted(User::ROLE_SUPER_ADMIN)) {
            $object->setAccessibility($accessibility);
            $object->setOwner($owner);
            $object->setGroups($groups);
        }

        $object->setProtected($protected);
        $object->setArchived($archived);
        $object->setType($type);
        if ($code !== null) {
            $object->setCode($code);
        }
        $object->setResumable($resumable == 1 || $resumable === true);
        $object->setSourceWizard($sourceWizard);

        $urlslug = ( trim((string) $urlslug) !== '' ) ? $this->slugifier->slugify($urlslug) :
                bin2hex($this->randomGenerator->nextBytes(16));

        $object->setSlug($urlslug);
        $slug_postfix = 2;

        // assuring that the slug is unique - with random one it's a bit unlikely, but with user input it's possible
        while ($this->getBySlug($object->getSlug(), $object->getId(), false)) {
            $object->setSlug($urlslug . '-' . $slug_postfix++);
        }

        return $this->resave($new, $user, $object, $serializedVariables, $errors);
    }

    public function resave($new, User $user, Test $object, $serializedVariables = null, $errors = array()) {
        $object->setUpdated();
        $object->setUpdatedBy($user);
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

        $this->repository->save($object);
        $this->repository->refresh($object);
        $object = $this->get($object->getId());
        $this->onObjectSaved($object, $new, $user, $serializedVariables);
        $this->repository->refresh($object);
        $object = $this->get($object->getId());

        return array("object" => $object, "errors" => $errors);
    }

    public function onObjectSaved($object, $is_new, User $user, $serializedVariables, $insert_initial_objects = true) {
        if ($object->getSourceWizard() != null) {
            if ($is_new) {
                $this->testVariableService->createVariablesFromSourceTest($user, $object);
            } else {
                $this->testVariableService->saveCollection($user, $serializedVariables, $object);
            }
        }
        if ($is_new && count($this->testVariableService->getBranches($object->getId())) == 0 && $insert_initial_objects) {
            $this->testVariableService->save($user, 0, "out", 2, "", false, 0, $object);
        }
        $this->updateDependentTests($user, $object->getId());

        if ($object->getType() == Test::TYPE_FLOW && $is_new && $insert_initial_objects) {
            $this->testNodeService->save($user, 0, 1, 15000, 15000, $object, $object, "");
            $this->testNodeService->save($user, 0, 2, 15500, 15100, $object, $object, "");
        }
    }

    public function markDependentTestsOutdated($object_id) {
        $this->repository->markDependentTestsOutdated($object_id);
    }

    public function updateDependentTests(User $user, $object_id) {
        $tests = $this->repository->findDependent($object_id);

        $result = array();
        foreach ($tests as $test) {
            $data = $this->resave(false, $user, $test);
            array_push($result, $data);
        }
        return $result;
    }

    public function delete($object_ids, $secure = true) {
        $object_ids = explode(",", $object_ids);

        $result = array();
        foreach ($object_ids as $object_id) {
            $object = $this->get($object_id, false, $secure);
            if ($object === null)
                continue;

            if ($object->isProtected() && $secure) {
                array_push($result, array("object" => $object, "errors" => array("validate.protected.mod")));
                continue;
            }
            $this->repository->delete($object);
            array_push($result, array("object" => $object, "errors" => array()));
        }
        return $result;
    }

    public function entityToArray(AEntity $ent) {
        $e = $ent->jsonSerialize();
        unset($e["logs"]);
        unset($e["slug"]);
        return $e;
    }

    public function importFromArray(User $user, $instructions, $obj, &$map, &$queue) {
        $pre_queue = array();
        if (!array_key_exists("Test", $map))
            $map["Test"] = array();
        if (array_key_exists("id" . $obj["id"], $map["Test"]))
            return array();

        $wizard = null;
        if ($obj["sourceWizard"]) {
            if (array_key_exists("TestWizard", $map) && array_key_exists("id" . $obj["sourceWizard"], $map["TestWizard"])) {
                $wizard_id = $map["TestWizard"]["id" . $obj["sourceWizard"]];
                $wizard = $this->testWizardRepository->find($wizard_id);
            }
            if (!$wizard) {
                array_push($pre_queue, $obj["sourceWizardObject"]);
            }
        }

        if (count($pre_queue) > 0) {
            return array("pre_queue" => $pre_queue);
        }

        $instruction = self::getObjectImportInstruction($obj, $instructions);
        $old_name = $instruction["existing_object"] ? $instruction["existing_object"]["name"] : null;
        $new_name = $this->getNextValidName($this->formatImportName($user, $instruction["rename"], $obj), $instruction["action"], $old_name);
        $result = array();
        $src_ent = $this->findConversionSource($obj, $map);
        if ($instruction["action"] == 1 && $src_ent)
            $result = $this->importConvert($user, $new_name, $src_ent, $obj, $map, $queue, $wizard);
        else if ($instruction["action"] == 2) {
            $map["Test"]["id" . $obj["id"]] = $obj["id"];
        } else
            $result = $this->importNew($user, $new_name, $obj, $map, $queue, $wizard);

        array_splice($queue, 1, 0, $obj["nodesConnections"]);
        array_splice($queue, 1, 0, $obj["nodes"]);
        array_splice($queue, 1, 0, $obj["variables"]);

        return $result;
    }

    protected function importNew(User $user, $new_name, $obj, &$map, &$queue, $wizard) {
        $ent = new Test();
        $ent->setName($new_name);
        $ent->setDescription($obj["description"]);
        $ent->setVisibility($obj["visibility"]);
        $ent->setType($obj["type"]);
        $ent->setCode($obj["code"]);
        $ent->setSourceWizard($wizard);
        $ent->setTags($obj["tags"]);
        $ent->setOwner($user);
        $ent->setResumable($obj["resumable"] == "1");
        $ent->setProtected($obj["protected"] == "1");
        $ent->setStarterContent($obj["starterContent"]);
        $ent->setAccessibility($obj["accessibility"]);
        if (array_key_exists("rev", $obj))
            $ent->setRevision($obj["rev"]);
        $ent_errors = $this->validator->validate($ent);
        $ent_errors_msg = array();
        foreach ($ent_errors as $err) {
            array_push($ent_errors_msg, $err->getMessage());
        }
        if (count($ent_errors_msg) > 0) {
            return array("errors" => $ent_errors_msg, "entity" => null, "source" => $obj);
        }
        $this->repository->save($ent);
        $map["Test"]["id" . $obj["id"]] = $ent->getId();
        return array("errors" => null, "entity" => $ent);
    }

    protected function findConversionSource($obj, $map) {
        return $this->get($obj["name"]);
    }

    protected function importConvert(User $user, $new_name, $src_ent, $obj, &$map, &$queue, $wizard) {
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
        $ent->setResumable($obj["resumable"] == "1");
        $ent->setProtected($obj["protected"] == "1");
        $ent->setStarterContent($obj["starterContent"]);
        $ent->setAccessibility($obj["accessibility"]);
        if (array_key_exists("rev", $obj))
            $ent->setRevision($obj["rev"]);
        else 
            $ent->setRevision(0);
        $ent_errors = $this->validator->validate($ent);
        $ent_errors_msg = array();
        foreach ($ent_errors as $err) {
            array_push($ent_errors_msg, $err->getMessage());
        }
        if (count($ent_errors_msg) > 0) {
            return array("errors" => $ent_errors_msg, "entity" => null, "source" => $obj);
        }
        $this->repository->save($ent);
        $map["Test"]["id" . $obj["id"]] = $ent->getId();

        $this->onObjectSaved($ent, false, $user, null, false);
        $this->onConverted($ent, $old_ent);

        return array("errors" => null, "entity" => $ent);
    }

    protected function onConverted($new_ent, $old_ent) {
        $this->clearNodes($old_ent->getId());
    }

    public function addFlowNode(User $user, $type, $posX, $posY, Test $flowTest, Test $sourceTest, $return_collections = false) {
        $result = $this->testNodeService->save($user, 0, $type, $posX, $posY, $flowTest, $sourceTest, "");
        if ($return_collections) {
            $result["collections"] = $this->getFlowCollections($flowTest->getId());
        }
        return $result;
    }

    public function removeFlowNode($node_ids, $return_collections = false) {
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

    public function moveFlowNode($nodes) {
        for ($i = 0; $i < count($nodes); $i++) {
            $node = $this->testNodeService->get($nodes[$i]["id"]);
            $node->setPosX($nodes[$i]["posX"]);
            $node->setPosY($nodes[$i]["posY"]);
            $this->testNodeService->repository->save($node);
        }
    }

    public function addFlowConnection(User $user, Test $flowTest, $sourceNode, $sourcePort, $destinationNode, $destinationPort, $returnFunction, $automatic, $return_collections = false) {
        $sourceNode = $this->testNodeService->get($sourceNode);
        $sourcePort = $this->testNodePortService->get($sourcePort);
        $destinationNode = $this->testNodeService->get($destinationNode);
        $destinationPort = $this->testNodePortService->get($destinationPort);
        $result = $this->testNodeConnectionService->save($user, 0, $flowTest, $sourceNode, $sourcePort, $destinationNode, $destinationPort, $returnFunction, $automatic);
        if ($return_collections) {
            $result["collections"] = $this->getFlowCollections($flowTest->getId());
        }
        return $result;
    }

    public function removeFlowConnection($connection_id, $return_collections = false) {
        $connection = $this->testNodeConnectionService->get($connection_id);
        $result = $this->testNodeConnectionService->delete($connection_id)[0];
        if ($return_collections) {
            $result["collections"] = $this->getFlowCollections($connection->getSourceNode()->getFlowTest()->getId());
        }
        return $result;
    }

    public function getFlowCollections($flow_id) {
        return array(
            "nodes" => $this->testNodeService->getByFlowTest($flow_id),
            "nodesConnections" => $this->testNodeConnectionService->getByFlowTest($flow_id)
        );
    }

    public function pasteNodes(User $user, Test $flowTest, $nodes, $return_collections = false) {
        foreach ($nodes as $node) {
            $result = $this->addFlowNode($user, $node["type"], $node["posX"], $node["posY"], $flowTest, $this->get($node["sourceTest"]), false);
            $new_node = $result["object"];

            foreach ($node["ports"] as $src_port) {
                foreach ($new_node->getPorts() as $dest_port) {
                    if ($src_port["variable"] !== null && $src_port["variable"] == $dest_port->getVariable()->getId()) {
                        $dest_port->setValue($src_port["value"]);
                        $dest_port->setString($src_port["string"]);
                        $this->testNodePortService->repository->save($dest_port);
                        break;
                    }
                }
            }
        }

        if ($return_collections) {
            $result["collections"] = $this->getFlowCollections($flowTest->getId());
        }
        return $result;
    }

    public function clearNodes($test_id) {
        $test = $this->get($test_id);
        if ($test) {
            foreach ($test->getNodes() as $node) {
                $this->testNodeService->delete($node->getId());
            }
        }
        //$this->testNodeService->repository->deleteByTest($test);
        return array("errors" => array());
    }

}
