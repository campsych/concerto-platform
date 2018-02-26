<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ImportService
{

    const MIN_EXPORT_VERSION = "5.0.beta.2.167";

    private $dataTableService;
    private $testService;
    private $testNodeService;
    private $testNodePortService;
    private $testNodeConnectionService;
    private $testVariableService;
    private $testWizardService;
    private $testWizardStepService;
    private $testWizardParamService;
    private $viewTemplateService;
    private $queue;
    private $map;
    private $version;
    private $entityManager;
    public $serviceMap;

    public function __construct(EntityManagerInterface $entityManager, DataTableService $dataTableService, TestService $testService, TestNodeService $testNodeService, TestNodePortService $testNodePortService, TestNodeConnectionService $testNodeConnectionService, TestVariableService $testVariableService, TestWizardService $testWizardService, TestWizardStepService $testWizardStepService, TestWizardParamService $testWizardParamService, ViewTemplateService $viewTemplateService, $version)
    {
        $this->entityManager = $entityManager;
        $this->dataTableService = $dataTableService;
        $this->testService = $testService;
        $this->testNodeService = $testNodeService;
        $this->testNodePortService = $testNodePortService;
        $this->testNodeConnectionService = $testNodeConnectionService;
        $this->testVariableService = $testVariableService;
        $this->testWizardService = $testWizardService;
        $this->testWizardStepService = $testWizardStepService;
        $this->testWizardParamService = $testWizardParamService;
        $this->viewTemplateService = $viewTemplateService;
        $this->version = $version;

        $this->map = array();
        $this->queue = array();
        $this->serviceMap = array(
            "DataTable" => $this->dataTableService,
            "Test" => $this->testService,
            "TestNode" => $this->testNodeService,
            "TestNodePort" => $this->testNodePortService,
            "TestNodeConnection" => $this->testNodeConnectionService,
            "TestVariable" => $this->testVariableService,
            "TestWizard" => $this->testWizardService,
            "TestWizardStep" => $this->testWizardStepService,
            "TestWizardParam" => $this->testWizardParamService,
            "ViewTemplate" => $this->viewTemplateService
        );
    }

    public static function is_array_assoc($arr)
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    public function isVersionValid($export_version)
    {
        $eve = explode(".", $export_version);
        $mve = explode(".", self::MIN_EXPORT_VERSION);

        for ($i = 0; $i < 2; $i++) {
            $ei = $eve[$i];
            $mi = $mve[$i];
            if ($ei > $mi)
                return true;
            if ($ei < $mi)
                return false;
        }
        if (count($ei) < count($mi))
            return true;
        if (count($ei) > count($mi))
            return false;

        for ($i = 2; $i < count($eve); $i++) {
            $ei = $eve[$i];
            $mi = $mve[$i];
            if ($ei > $mi)
                return true;
            if ($ei < $mi)
                return false;
        }
        return true;
    }

    public function getImportFileContents($file, $unlink = true)
    {
        $file_content = file_get_contents($file);
        $data = json_decode($file_content, true);
        if (is_null($data)) {
            $data = json_decode(gzuncompress($file_content), true);
        }
        unset($file_content);
        if ($unlink) {
            unlink($file);
        }
        return $data;
    }

    private function getAllTopObjectsFromImportData($data, &$top_data)
    {
        foreach ($data as $obj) {
            if (array_key_exists("class_name", $obj) && in_array($obj["class_name"], array(
                    "DataTable",
                    "Test",
                    "TestWizard",
                    "ViewTemplate"
                ))) {
                $found = false;
                foreach ($top_data as $cur_obj) {
                    if ($cur_obj["class_name"] == $obj["class_name"] && $cur_obj["id"] == $obj["id"]) {
                        $found = true;
                        break;
                    }
                }
                if (!$found)
                    array_push($top_data, $obj);
            }

            foreach ($obj as $k => $v) {
                if (is_array($v) && self::is_array_assoc($v)) {
                    $this->getAllTopObjectsFromImportData(array($v), $top_data);
                } else if (is_array($v)) {
                    $this->getAllTopObjectsFromImportData($v, $top_data);
                }
            }
        }
    }

    public function getPreImportStatus($data)
    {
        $top_data = array();
        $this->getAllTopObjectsFromImportData($data, $top_data);
        $result = array();
        foreach ($top_data as $imported_object) {
            $service = $this->serviceMap[$imported_object["class_name"]];
            $existing_entity = $service->repository->findOneBy(array("name" => $imported_object["name"]));
            if ($existing_entity !== null) {
                $existing_entity = $service->get($existing_entity->getId());
            }

            $can_ignore = false;
            if ($existing_entity != null) {
                $existing_entity_array = $existing_entity->jsonSerialize();
                $class = "\\Concerto\\PanelBundle\\Entity\\" . $existing_entity_array["class_name"];
                $existing_entity_hash = $class::getArrayHash($existing_entity_array);

                //same hash
                if (array_key_exists("hash", $imported_object) && $existing_entity_hash == $imported_object["hash"])
                    $can_ignore = true;
            }

            $default_action = "0";
            if ($can_ignore)
                $default_action = "2";
            else if ($existing_entity != null)
                $default_action = "1";

            $obj_status = array(
                "id" => $imported_object["id"],
                "name" => $imported_object["name"],
                "class_name" => $imported_object["class_name"],
                "action" => $default_action,
                "rename" => $imported_object["name"],
                "starter_content" => $imported_object["starterContent"],
                "existing_object" => $existing_entity ? true : false,
                "existing_object_name" => $existing_entity ? $existing_entity->getName() : null,
                "can_ignore" => $can_ignore
            );
            array_push($result, $obj_status);
        }
        return $result;
    }

    public function getPreImportStatusFromFile($file)
    {
        $data = $this->getImportFileContents($file, false);
        $valid = array_key_exists("version", $data) && $this->isVersionValid($data["version"]);
        if (!$valid)
            return array("result" => 2);
        return array("result" => 0, "status" => $this->getPreImportStatus($data["collection"]));
    }

    public function reset()
    {
        $this->map = array();
    }

    public function importFromFile(User $user, $file, $instructions, $unlink = true)
    {
        $data = $this->getImportFileContents($file, $unlink);
        $valid = array_key_exists("version", $data) && $this->isVersionValid($data["version"]);
        if (!$valid)
            return array("result" => 2);
        return $this->import($user, $instructions, $data["collection"]);
    }

    public function import(User $user, $instructions, $data)
    {
        $result = array("result" => 0, "import" => array());
        $this->queue = $data;
        while (count($this->queue) > 0) {
            $obj = $this->queue[0];
            if (is_array($obj) && array_key_exists("class_name", $obj)) {
                $service = $this->serviceMap[$obj["class_name"]];
                $last_result = $service->importFromArray($user, $instructions, $obj, $this->map, $this->queue);
                if (array_key_exists("errors", $last_result) && $last_result["errors"] != null) {
                    array_push($result["import"], $last_result);
                    $result["result"] = 1;
                    return $result;
                }
                if (array_key_exists("pre_queue", $last_result) && count($last_result["pre_queue"]) > 0) {
                    $this->queue = array_merge($last_result["pre_queue"], $this->queue);
                } else {
                    array_push($result["import"], $last_result);
                    array_shift($this->queue);
                }
            }
        }
        $this->entityManager->flush();
        $this->reset();
        return $result;
    }

    public function copy($class_name, User $user, $object_id, $name)
    {
        $ent = $this->serviceMap[$class_name]->get($object_id);
        $dependencies = array();
        $ent->jsonSerialize($dependencies);

        $collection = $dependencies["collection"];
        for ($i = 0; $i < count($collection); $i++) {
            $elem = $collection[$i];
            $elem_class = $elem["class_name"];
            $collection[$i] = $this->serviceMap[$elem_class]->convertToExportable($elem);
        }

        $instructions = $this->getPreImportStatus($collection);
        for ($i = 0; $i < count($instructions); $i++) {
            if ($instructions[$i]["id"] == $object_id && $instructions[$i]["class_name"] == $class_name) {
                $instructions[$i]["rename"] = $name;
                $instructions[$i]["action"] = "0";
            } else {
                $instructions[$i]["action"] = "2";
            }
        }
        $result = $this->import(
            $user, //
            json_decode(json_encode($instructions), true), //
            $collection
        );
        return $result;
    }

}
