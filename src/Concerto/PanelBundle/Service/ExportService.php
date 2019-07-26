<?php

namespace Concerto\PanelBundle\Service;

use Symfony\Component\Yaml\Yaml;

class ExportService
{
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
    private $version;
    public $serviceMap;

    public function __construct(DataTableService $dataTableService, TestService $testService, TestNodeService $testNodeService, TestNodePortService $testNodePortService, TestNodeConnectionService $testNodeConnectionService, TestVariableService $testVariableService, TestWizardService $testWizardService, TestWizardStepService $testWizardStepService, TestWizardParamService $testWizardParamService, ViewTemplateService $viewTemplateService, $version)
    {
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

    public function getInitialExportInstructions($class, $object_ids)
    {
        $instructions = array();
        $collection = $this->getExportCollection($class, $object_ids);
        foreach ($collection as $object) {
            $data = '0';
            $dataNum = "";
            if ($object["class_name"] == "DataTable") {
                $service = $this->serviceMap["DataTable"];
                $data = '1';
                $dataNum = $service->dbDataDao->countMatchingData($object["name"], null);
            }

            $objectInstructions = array(
                "id" => $object["id"],
                "name" => $object["name"],
                "class_name" => $object["class_name"],
                "data" => $data,
                "data_num" => $dataNum
            );
            array_push($instructions, $objectInstructions);
        }
        return $instructions;
    }

    public function addExportDependency($id, $sectionService, &$dependencies, $secure = true)
    {
        $entity = $sectionService->get($id, false, $secure);
        if (!$entity)
            return false;
        $entity->jsonSerialize($dependencies);

        if (array_key_exists("ids", $dependencies)) {
            foreach ($dependencies["ids"] as $k => $v) {
                $ids_service = $this->serviceMap[$k];
                foreach ($v as $id) {
                    $ent = $ids_service->get($id, false, $secure);
                    if ($ent)
                        $ent->jsonSerialize($dependencies);
                }
            }
        }
        return true;
    }

    public function convertCollectionToExportable($collection, $instructions, $secure = true, $addHash = true)
    {
        $result = array();
        foreach ($collection as $elem) {
            $export_elem = $elem;
            $elem_service = $this->serviceMap[$elem["class_name"]];
            $elem_class = "\\Concerto\\PanelBundle\\Entity\\" . $elem["class_name"];
            if($addHash) {
                $export_elem["hash"] = $elem_class::getArrayHash($elem);
            }

            $elemInstruction = null;
            if ($instructions !== null) {
                foreach ($instructions as $ins) {
                    if ($ins["class_name"] == $elem["class_name"] && array_key_exists("id", $ins) && $ins["id"] == $elem["id"]) {
                        $elemInstruction = $ins;
                        break;
                    }
                    if ($ins["class_name"] == $elem["class_name"] && array_key_exists("name", $ins) && $ins["name"] == $elem["name"]) {
                        $elemInstruction = $ins;
                        break;
                    }
                }
            }

            $export_elem = $elem_service->convertToExportable($export_elem, $elemInstruction, $secure);
            if (in_array($elem["class_name"], array(
                "DataTable",
                "ViewTemplate"
            ))) {
                array_unshift($result, $export_elem);
            } else {
                array_push($result, $export_elem);
            }
        }
        return $result;
    }

    private function getExportCollection($class, $object_ids, $instructions = null)
    {
        $dependencies = array();
        $section_service = $this->serviceMap[$class];

        if ($object_ids !== null) {
            $object_ids = explode(",", $object_ids);
            foreach ($object_ids as $object_id) {
                $this->addExportDependency($object_id, $section_service, $dependencies);
            }
        } else if ($instructions !== null) {
            foreach ($instructions as $ins) {
                if ($ins["class_name"] == $class) {
                    $this->addExportDependency($ins["name"], $section_service, $dependencies);
                }
            }
        }

        $collection = array();
        if (array_key_exists("collection", $dependencies)) {
            $collection = $this->convertCollectionToExportable($dependencies["collection"], $instructions);
        }
        return $collection;
    }

    public function exportToFile($class, $instructions, $format = "yml")
    {
        $collection = $this->getExportCollection($class, null, $instructions);

        $result = array("version" => $this->version, "collection" => $collection);
        switch ($format) {
            case "json":
                return json_encode($result, JSON_PRETTY_PRINT);
            case "compressed":
                return gzcompress(json_encode($result, JSON_PRETTY_PRINT), 1);
            default: //yaml
                return Yaml::dump($result, 100, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
        }
    }

    public function decompactExportInstructions($compactInstructions)
    {
        $fullInstructions = [];
        foreach ($compactInstructions as $class => $sets) {
            for ($i = 0; $i < count($sets["id"]); $i++) {
                $instruction = array(
                    "class_name" => $class,
                    "id" => $sets["id"][$i],
                    "data" => $sets["data"][$i],
                    "name" => $sets["name"][$i]
                );
                array_push($fullInstructions, $instruction);
            }
        }
        return $fullInstructions;
    }
}
