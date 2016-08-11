<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Entity\User;

class ImportService {

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
    public $serviceMap;

    public function __construct(DataTableService $dataTableService, TestService $testService, TestNodeService $testNodeService, TestNodePortService $testNodePortService, TestNodeConnectionService $testNodeConnectionService, TestVariableService $testVariableService, TestWizardService $testWizardService, TestWizardStepService $testWizardStepService, TestWizardParamService $testWizardParamService, ViewTemplateService $viewTemplateService) {
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
    
    public static function is_array_assoc($arr) {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    public function getImportFileContents($file, $unlink = true) {
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
    
    private function getAllTopObjectsFromImportData($data, &$top_data) {
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

    public function getPreImportStatus($file) {
        $data = $this->getImportFileContents($file, false);
        $top_data = array();
        $this->getAllTopObjectsFromImportData($data, $top_data);
        $result = array();
        foreach ($top_data as $imported_object) {
            $service = $this->serviceMap[$imported_object["class_name"]];
            $new_revision = array_key_exists("revision", $imported_object) ? $imported_object["revision"] : 0;
            $existing_entity = $service->repository->findOneBy(array("name" => $imported_object["name"]));
            if ($existing_entity !== null) {
                $existing_entity = $service->get($existing_entity->getId());
            }

            $obj_status = array(
                "id" => $imported_object["id"],
                "name" => $imported_object["name"],
                "class_name" => $imported_object["class_name"],
                "action" => "0",
                "rename" => $imported_object["name"],
                "revision" => $new_revision,
                "starter_content" => $imported_object["starterContent"],
                "existing_object" => $existing_entity
            );
            array_push($result, $obj_status);
        }
        return $result;
    }

    public function reset() {
        $this->map = array();
    }

    public function importFromFile(User $user, $file, $instructions, $unlink = true) {
        $data = $this->getImportFileContents($file, $unlink);
        return $this->import($user, $instructions, $data);
    }

    public function import(User $user, $instructions, $data) {
        $result = array();
        $this->queue = $data;
        while (count($this->queue) > 0) {
            $obj = $this->queue[0];
            if (array_key_exists("class_name", $obj)) {
                $service = $this->serviceMap[$obj["class_name"]];
                $last_result = $service->importFromArray($user, $instructions, $obj, $this->map, $this->queue);
                if (array_key_exists("errors", $last_result) && $last_result["errors"] != null) {
                    array_push($result, $last_result);
                    return $result;
                }
                if (array_key_exists("pre_queue", $last_result) && count($last_result["pre_queue"]) > 0) {
                    $this->queue = array_merge($last_result["pre_queue"], $this->queue);
                } else {
                    array_push($result, $last_result);
                    array_shift($this->queue);
                }
            }
        }
        return $result;
    }

    public function copy($class_name, User $user, $object_id, $name) {

        $arr = array(json_decode(json_encode($this->serviceMap[$class_name]->entityToArray($this->serviceMap[$class_name]->get($object_id))), true));
        $result = $this->import(
                $user, //
                $name, //
                $arr
        );
        return $result;
    }

}
