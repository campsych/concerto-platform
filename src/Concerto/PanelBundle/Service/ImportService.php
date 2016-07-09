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

    public function importFromFile(User $user, $file, $name = "", $unlink = true) {
        $file_content = file_get_contents($file);

        $imports = json_decode($file_content, true);
        if (is_null($imports)) {
            $imports = json_decode(gzuncompress($file_content), true);
        }

        unset($file_content);
        if ($unlink) {
            unlink($file);
        }
        return $this->import($user, $name, $imports);
    }
    
    public function reset(){
        $this->map = array();
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
