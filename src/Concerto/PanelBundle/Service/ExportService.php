<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Entity\User;

class ExportService {

    const FORMAT_COMPRESSED = 'compressed';
    const FORMAT_PLAINTEXT = 'text';

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

    public function exportToFile($class, $object_ids, $format = self::FORMAT_COMPRESSED) {
        $result = array();
        $object_ids = explode(",", $object_ids);
        $dependencies = array();
        $section_service = $this->serviceMap[$class];
        foreach ($object_ids as $object_id) {
            $entity = $section_service->get($object_id);
            $entity->jsonSerialize($dependencies);

            if (array_key_exists("ids", $dependencies)) {
                foreach ($dependencies["ids"] as $k => $v) {
                    $ids_service = $this->serviceMap[$k];
                    foreach ($v as $id) {
                        $ids_service->get($id)->jsonSerialize($dependencies);
                    }
                }
            }

            if (array_key_exists("collection", $dependencies)) {
                foreach ($dependencies["collection"] as $elem) {
                    $export_elem = $elem;
                    $elem_service = $this->serviceMap[$elem["class_name"]];
                    $elem_class = "\\Concerto\\PanelBundle\\Entity\\" . $elem["class_name"];
                    $export_elem["hash"] = $elem_class::getArrayHash($elem);
                    $export_elem = $elem_service->convertToExportable($export_elem);
                    array_push($result, $export_elem);
                }
            }
        }
        if ($format === self::FORMAT_COMPRESSED)
            return gzcompress(json_encode($result, JSON_PRETTY_PRINT), 1);
        else
            return json_encode($result, JSON_PRETTY_PRINT);
    }

    public function exportNodeToFile($object_ids, $format = ExportService::FORMAT_COMPRESSED) {
        $object_ids = explode(",", $object_ids);
        $dependencies = array();
        foreach ($object_ids as $object_id) {
            $node = $this->testNodeService->get($object_id);

            $test = $node->getSourceTest();
            if ($node->getTitle() != "")
                $test->setName($node->getTitle());
            foreach ($node->getPorts() as $port) {
                foreach ($test->getVariables() as $var) {
                    if ($port->getVariable()->getId() == $var->getId()) {
                        $var->setValue($port->getValue());
                        break;
                    }
                }
            }
            $test->jsonSerialize($dependencies);
        }

        if (array_key_exists("ids", $dependencies)) {
            foreach ($dependencies["ids"] as $k => $v) {
                $ids_service = $this->serviceMap[$k];
                foreach ($v as $id) {
                    $ids_service->get($id)->jsonSerialize($dependencies);
                }
            }
        }

        $result = array();
        if (array_key_exists("collection", $dependencies)) {
            foreach ($dependencies["collection"] as $elem) {
                $export_elem = $elem;
                $elem_service = $this->serviceMap[$elem["class_name"]];
                $elem_class = "\\Concerto\\PanelBundle\\Entity\\" . $elem["class_name"];
                $export_elem["hash"] = $elem_class::getArrayHash($elem);
                $export_elem = $elem_service->convertToExportable($export_elem);
                array_push($result, $export_elem);
            }
        }

        if ($format === ExportService::FORMAT_COMPRESSED)
            return gzcompress(json_encode($result, JSON_PRETTY_PRINT), 1);
        else
            return json_encode($result, JSON_PRETTY_PRINT);
    }

}
