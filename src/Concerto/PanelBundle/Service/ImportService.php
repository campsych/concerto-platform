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

    public function getPreImportStatus($file) {
        $data = $this->getImportFileContents($file, false);
        $result = array();
        foreach ($data as $imported_object) {
            $service = $this->serviceMap[$imported_object["class_name"]];
            $existing_entity = $service->repository->findOneBy(array("name" => $imported_object["name"]));
            if ($existing_entity !== null)
                $existing_entity = $service->get($existing_entity->getId());
            $obj_status = array(
                "id" => $imported_object["id"],
                "name" => $imported_object["name"],
                "class_name" => $imported_object["class_name"],
                "new_name" => $imported_object["name"],
                "revision" => array_key_exists("revision", $imported_object) ? $imported_object["revision"] : 0,
                "existing_object" => $existing_entity
            );
            array_push($result, $obj_status);
        }
        return $result;
    }

    public function reset() {
        $this->map = array();
    }

    public function importFromFile(User $user, $file, $name = "", $unlink = true) {
        $data = $this->getImportFileContents($file, $unlink);
        return $this->import($user, $name, $data);
    }

    public function import(User $user, $name, $data) {
        $result = array();
        $this->queue = $data;
        while (count($this->queue) > 0) {
            $obj = $this->queue[0];
            if (array_key_exists("class_name", $obj)) {
                $service = $this->serviceMap[$obj["class_name"]];
                $last_result = $service->importFromArray($user, $name, $obj, $this->map, $this->queue);
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
