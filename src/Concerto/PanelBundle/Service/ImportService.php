<?php

namespace Concerto\PanelBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;

class ImportService
{

    const MIN_EXPORT_VERSION = "5.0.beta.8.1";

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
    private $renames;
    private $version;
    private $entityManager;
    private $adminService;
    private $kernel;
    public $serviceMap;

    public function __construct(
        EntityManagerInterface $entityManager,
        DataTableService $dataTableService,
        TestService $testService,
        TestNodeService $testNodeService,
        TestNodePortService $testNodePortService,
        TestNodeConnectionService $testNodeConnectionService,
        TestVariableService $testVariableService,
        TestWizardService $testWizardService,
        TestWizardStepService $testWizardStepService,
        TestWizardParamService $testWizardParamService,
        ViewTemplateService $viewTemplateService,
        $version,
        AdministrationService $adminService,
        KernelInterface $kernel
    )
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
        $this->adminService = $adminService;
        $this->kernel = $kernel;

        $this->map = array();
        $this->renames = array();
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

        if (count($eve) < count($mve))
            return true;
        if (count($eve) > count($mve))
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
        $extension = pathinfo($file)["extension"];
        $data = null;
        switch ($extension) {
            case "concerto":
                $data = json_decode(gzuncompress($file_content), true);
                break;
            case "yml":
            case "yaml":
                $data = Yaml::parse($file_content);
                break;
            default:
                $data = json_decode($file_content, true);
                break;
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
            if (isset($obj["class_name"]) && in_array($obj["class_name"], array(
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
                $existing_entity_hash = $existing_entity->getEntityHash();

                //same hash
                if (isset($imported_object["hash"]) && $existing_entity_hash == $imported_object["hash"])
                    $can_ignore = true;
            }

            $default_action = "0";
            if ($can_ignore)
                $default_action = "2";
            else if ($existing_entity != null)
                $default_action = "1";
            $data = "0";
            $data_num = 0;
            if ($imported_object["class_name"] == "DataTable") {
                $data = "1";
                if (isset($imported_object["data"]) && $imported_object["data"] !== null) {
                    //value will be zero when data set in external file
                    $data_num = count($imported_object["data"]);
                }
            }

            $obj_status = array(
                "id" => $imported_object["id"],
                "name" => $imported_object["name"],
                "class_name" => $imported_object["class_name"],
                "action" => $default_action,
                "rename" => $imported_object["name"],
                "starter_content" => $imported_object["starterContent"],
                "existing_object" => $existing_entity ? true : false,
                "existing_object_name" => $existing_entity ? $existing_entity->getName() : null,
                "can_ignore" => $can_ignore,
                "data" => $data,
                "data_num" => $data_num
            );
            array_push($result, $obj_status);
        }
        return $result;
    }

    public function getPreImportStatusFromFile($file, &$errorMessages = null)
    {
        $data = $this->getImportFileContents($file, false);
        $valid = isset($data["version"]) && $this->isVersionValid($data["version"]);
        if (!$valid) {
            $errorMessages = ["import.incompatible_version"];
            return false;
        }

        return $this->getPreImportStatus($data["collection"]);
    }

    public function reset()
    {
        $this->map = array();
        $this->renames = array();
        //$this->entityManager->clear();
    }

    public function importFromFile($file, $instructions, $unlink = true, &$errorMessages = null)
    {
        $dir = pathinfo($file)["dirname"];
        $data = $this->getImportFileContents($file, $unlink);
        $valid = isset($data["version"]) && $this->isVersionValid($data["version"]);
        if (!$valid) {
            $errorMessages = ["import.incompatible_version"];
            return false;
        }

        foreach ($data["collection"] as &$obj) {
            $instruction = ASectionService::getObjectImportInstruction($obj, $instructions);
            if (isset($instruction["src"]) && $instruction["src"] == 1) {
                $this->mergeExternalSource($obj, $dir . "/src");
            }
        }

        return $this->import($instructions, $data["collection"], $errorMessages);
    }

    public function scheduleTaskImportContent($file, $exportInstructions, $scheduled, &$output = null, &$errors = null)
    {
        if ($scheduled && $this->adminService->isTaskScheduled()) {
            $errors[] = "tasks.already_scheduled";
            return false;
        }
        if (!$this->adminService->canDoMassContentModifications()) {
            $errors[] = "tasks.locked";
            return false;
        }

        $app = new Application($this->kernel);
        $app->setAutoExit(false);
        $in = new ArrayInput([
            "command" => "concerto:task:content:import",
            "input" => $file,
            "--instant-run" => !$scheduled,
            "--instructions" => $exportInstructions
        ]);
        $out = new BufferedOutput();
        $returnCode = $app->run($in, $out);
        $output .= $out->fetch();
        return $returnCode === 0;
    }

    private function canCollectionBeModified($collection, $instructions, &$errorMessages)
    {
        foreach ($collection as $object) {
            /** @var AExportableSectionService $objectService */
            $objectService = $this->serviceMap[$object["class_name"]];
            $instruction = $objectService::getObjectImportInstruction($object, $instructions);
            //if convert
            if ($instruction["action"] == 1 && !$objectService->canBeModified($object["name"], time(), $errorMessages)) {
                return false;
            }
        }
        return true;
    }

    private function mergeExternalSource(&$obj, $srcDir)
    {
        switch ($obj["class_name"]) {
            case "Test":
            {
                //code based
                if ($obj["type"] == 0 && $obj["code"] === null) {
                    $path = $srcDir . "/" . ExportService::getTestCodeFilename($obj);
                    if (file_exists($path)) {
                        $value = file_get_contents($path);
                        if ($value !== false) {
                            $obj["code"] = $value;
                        }
                    }
                }

                //ports
                foreach ($obj["nodes"] as &$node) {
                    foreach ($node["ports"] as &$port) {
                        //only input ports
                        if ($port["type"] == 0 && $port["value"] === null) {
                            $path = $srcDir . "/" . ExportService::getPortValueFilename($obj, $node, $port);
                            if (file_exists($path)) {
                                $value = file_get_contents($path);
                                if ($value !== false) {
                                    $port["value"] = $value;
                                }
                            }
                        }
                    }
                }
                break;
            }
            case "ViewTemplate":
            {
                //css
                if ($obj["css"] === null) {
                    $path = $srcDir . "/" . ExportService::getTemplateCssFilename($obj);
                    if (file_exists($path)) {
                        $value = file_get_contents($path);
                        if ($value !== false) {
                            $obj["css"] = $value;
                        }
                    }
                }
                //js
                if ($obj["js"] === null) {
                    $path = $srcDir . "/" . ExportService::getTemplateJsFilename($obj);
                    if (file_exists($path)) {
                        $value = file_get_contents($path);
                        if ($value !== false) {
                            $obj["js"] = $value;
                        }
                    }
                }
                //html
                if ($obj["html"] === null) {
                    $path = $srcDir . "/" . ExportService::getTemplateHtmlFilename($obj);
                    if (file_exists($path)) {
                        $value = file_get_contents($path);
                        if ($value !== false) {
                            $obj["html"] = $value;
                        }
                    }
                }
                break;
            }
            case "DataTable":
            {
                //data
                if (isset($obj["data"]) && $obj["data"] === null) {
                    $path = $srcDir . "/" . ExportService::getTableDataFilename($obj);
                    if (file_exists($path)) {
                        $obj["data"] = Yaml::parseFile($path);
                    }
                }
                break;
            }
        }
    }

    public function import($instructions, $data, &$errorMessages = null, &$lastTopObjectImported = null)
    {
        $this->queue = $data;
        while (count($this->queue) > 0) {
            $obj = $this->queue[0];
            if (is_array($obj) && isset($obj["class_name"])) {
                $service = $this->serviceMap[$obj["class_name"]];
                $lastResult = $service->importFromArray($instructions, $obj, $this->map, $this->renames, $this->queue);
                if (isset($lastResult["errors"]) && $lastResult["errors"] != null) {
                    $errorMessages = $lastResult["errors"];
                    return false;
                }
                if (isset($lastResult["pre_queue"]) && count($lastResult["pre_queue"]) > 0) {
                    $this->queue = array_merge($lastResult["pre_queue"], $this->queue);
                } else {
                    array_shift($this->queue);
                }
                if (in_array($obj["class_name"], ["DataTable", "Test", "TestWizard", "ViewTemplate"])) $lastTopObjectImported = $lastResult["entity"];
            }
        }
        $this->entityManager->flush();
        $this->reset();
        return true;
    }

    public function copy($class_name, $object_id, $name, &$errorMessages = null, &$newObject = null)
    {
        $ent = $this->serviceMap[$class_name]->get($object_id);
        $dependencies = array();
        $ent->jsonSerialize($dependencies);

        $collection = $dependencies["collection"];
        for ($i = 0; $i < count($collection); $i++) {
            $elem = $collection[$i];
            $elem_class = $elem["class_name"];
            $collection[$i] = $this->serviceMap[$elem_class]->convertToExportable($elem, array("data" => "2"));
        }

        $instructions = $this->getPreImportStatus($collection);
        for ($i = 0; $i < count($instructions); $i++) {
            if ($instructions[$i]["id"] == $object_id && $instructions[$i]["class_name"] == $class_name) {
                $instructions[$i]["rename"] = $name;
                $instructions[$i]["action"] = "0";
            } else {
                $instructions[$i]["action"] = "2";
            }
            $instructions[$i]["data"] = "2";
        }
        return $this->import(
            json_decode(json_encode($instructions), true),
            $collection,
            $errorMessages,
            $newObject
        );
    }

}
