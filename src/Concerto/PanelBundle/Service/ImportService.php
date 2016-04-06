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

    public function import(User $user, $name, $data) {
        $result = array();
        foreach ($data as $obj) {
            if (array_key_exists("class_name", $obj)) {
                $service = $this->serviceMap[$obj["class_name"]];
                $dependants_missing = true;
                while ($dependants_missing) {
                    $last_result = $service->importFromArray($user, $name, $obj, $this->map, $this->queue);
                    if (array_key_exists("errors", $last_result) && $last_result["errors"] != null) {
                        array_push($result, $last_result);
                        return $result;
                    }
                    if (array_key_exists("pre_queue", $last_result) && count($last_result["pre_queue"]) > 0) {
                        $result = array_merge($result, $this->import($user, $name, $last_result["pre_queue"]));
                    } else {
                        $dependants_missing = false;
                        array_push($result, $last_result);
                    }
                }
            }
        }
        while (count($this->queue) > 0) {
            $queue = $this->queue;
            $this->queue = array();
            $r = $this->import($user, $name, $queue);
            $result = array_merge($result, $r);
        }
        return $result;
    }

    public function copyAction($class_name, User $user, $object_id, $name) {

        $arr = array($this->serviceMap[$class_name]->entityToArray($this->serviceMap[$class_name]->get($object_id)));
        $result = $this->import(
                $user, //
                $name, //
                $arr
        );
        return $result;
    }

}
