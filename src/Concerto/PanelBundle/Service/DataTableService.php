<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\DAO\DBDataDAO;
use Concerto\PanelBundle\Entity\DataTable;
use Concerto\PanelBundle\Repository\DataTableRepository;
use Concerto\PanelBundle\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DataTableService extends AExportableSectionService
{

    public $dbStructureService;
    public $dbDataDao;
    private $testWizardParamService;

    public function __construct(
        DataTableRepository $repository,
        ValidatorInterface $validator,
        DBStructureService $dbStructureService,
        DBDataDAO $dbDataDao,
        AuthorizationCheckerInterface $securityAuthorizationChecker,
        TestWizardParamService $testWizardParamService,
        TokenStorageInterface $securityTokenStorage,
        AdministrationService $administrationService,
        LoggerInterface $logger)
    {
        parent::__construct($repository, $validator, $securityAuthorizationChecker, $securityTokenStorage, $administrationService, $logger);

        $this->dbStructureService = $dbStructureService;
        $this->dbDataDao = $dbDataDao;
        $this->testWizardParamService = $testWizardParamService;
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
            $object = new DataTable();
        }
        if ($object !== null)
            $this->assignColumnCollection(array($object));

        return $object;
    }

    public function save($object_id, $name, $description, $accessibility, $archived, $owner, $groups)
    {
        $user = null;
        $token = $this->securityTokenStorage->getToken();
        if ($token !== null) $user = $token->getUser();

        $errors = array();
        $object = $this->get($object_id);
        $new = false;
        $oldName = null;
        if ($object !== null) {
            $oldName = $object->getName();
        } else {
            $new = true;
            $object = new DataTable();
            $object->setOwner($user);
        }
        $object->setName($name);
        if ($description !== null) {
            $object->setDescription($description);
        }

        if (!self::$securityOn || $this->securityAuthorizationChecker->isGranted(User::ROLE_SUPER_ADMIN)) {
            $object->setAccessibility($accessibility);
            $object->setOwner($owner);
            $object->setGroups($groups);
        }

        $object->setArchived($archived);
        foreach ($this->validator->validate($object) as $err) {
            array_push($errors, $err->getMessage());
        }
        if (count($errors) > 0) {
            return array("object" => null, "errors" => $errors);
        }

        if ($new) {
            $errors = $this->dbStructureService->createDefaultTable($name);
        } else if ($oldName != $name) {
            $errors = $this->dbStructureService->renameTable($oldName, $name);
        }
        if (count($errors) > 0) {
            return array("object" => null, "errors" => $errors);
        }

        $this->update($object);
        return array("object" => $object, "errors" => $errors);
    }

    public function update(DataTable $obj, $flush = true)
    {
        $user = null;
        $token = $this->securityTokenStorage->getToken();
        if ($token !== null) $user = $token->getUser();

        $obj->setUpdatedBy($user);
        $isNew = $obj->getId() === null;
        $changeSet = $this->repository->getChangeSet($obj);
        if ($isNew || !empty($changeSet)) {
            $this->repository->save($obj, $flush);
            $isRenamed = !$isNew && isset($changeSet["name"]);
            if ($isRenamed) {
                $this->testWizardParamService->onObjectRename($obj, $changeSet["name"][0]);
            }
        }
    }

    public function convertToExportable($array, $instruction = null, $secure = true)
    {
        $array = parent::convertToExportable($array, $instruction, $secure);

        if ($instruction !== null) {
            //include data
            if (isset($instruction["data"]) && $instruction["data"] == 2) {
                $array["data"] = $this->getFilteredData($array["name"], false, null, $secure);
            }
        }

        return $array;
    }

    public function delete($object_ids, $secure = true)
    {
        $object_ids = explode(",", $object_ids);

        $result = array();
        foreach ($object_ids as $object_id) {
            $object = $this->get($object_id, false, $secure);
            if (!$object)
                continue;
            if ($object != null) {
                $this->dbStructureService->removeTable($object->getName());
            }
            $this->repository->delete($object);
            array_push($result, array("object" => $object, "errors" => array()));
        }
        return $result;
    }

    public function getColumns($object_id)
    {
        $object = $this->get($object_id);
        if ($object != null) {
            return $this->dbStructureService->getColumns($object->getName());
        } else {
            return array();
        }
    }

    public function getColumn($object_id, $column_name)
    {
        $object = $this->get($object_id);
        if ($object != null) {
            return $this->dbStructureService->getColumn($object->getName(), $column_name);
        } else {
            return array();
        }
    }

    public function getFilteredData($object_id, $prefixed = false, $filters = null, $secure = true)
    {
        $object = $this->get($object_id, false, $secure);
        if ($object != null) {
            if (!$this->validateFilters($object->getName(), $filters)) return array();

            $data = $this->dbDataDao->fetchMatchingData($object->getName(), $filters);
            if ($prefixed) {
                self::prefixData($data);
            }
            return $data;
        }
        return array();
    }

    public function countFilteredData($object_id, $filters)
    {
        $object = $this->get($object_id);
        if ($object != null) {
            if (!$this->validateFilters($object->getName(), $filters)) return 0;

            return $this->dbDataDao->countMatchingData($object->getName(), $filters);
        }
        return 0;
    }

    private function validateFilters($tableName, $filters)
    {
        $decodedFilters = $filters !== null ? json_decode($filters, true) : null;
        if ($decodedFilters !== null) {
            foreach (array_keys($decodedFilters["filters"]) as $filterElem) {
                if (empty($this->dbStructureService->getColumn($tableName, $filterElem))) return false;
            }
            foreach (array_keys($decodedFilters["sorting"]) as $sortingElem) {
                if (empty($this->dbStructureService->getColumn($tableName, $sortingElem))) return false;
            }
        }
        return true;
    }

    public function streamJsonData($object_id, $prefixed = false)
    {
        $object = $this->get($object_id);
        if ($object != null) {
            $result = $this->dbDataDao->getFilteredDataResult($object->getName());
            $j = 0;
            echo "[";
            while ($row = $result->fetch()) {
                if ($j > 0) {
                    echo ",";
                }
                if ($prefixed) {
                    foreach ($row as $k => $v) {
                        $row["col_" . $k] = $v;
                        unset($row[$k]);
                    }
                }
                echo json_encode($row);
                ob_flush();
                flush();
                $j++;
            }
            echo "]";
        }
    }

    public function streamCsvData($table_id)
    {
        $object = $this->get($table_id);
        if ($object) {
            $fh = fopen('php://output', 'w');
            $cols = $object->getColumns();
            $header = array();
            foreach ($cols as $col) {
                array_push($header, $col["name"]);
            }
            fputcsv($fh, $header, ',', '"', "\0");
            $iterator = $this->dbDataDao->getFilteredDataResult($object->getName());
            while (($row = $iterator->fetch()) !== false) {
                fputcsv($fh, $row, ',', '"', "\0");
            }
            fclose($fh);
        }
    }

    public function deleteColumns($object_id, $column_names)
    {
        $object = $this->get($object_id);
        if ($object != null) {
            $names = array();
            if (!is_array($column_names)) {
                $names = explode(",", $column_names);
            } else {
                $names = $column_names;
            }
            foreach ($names as $n) {
                $this->dbStructureService->removeColumn($object->getName(), trim($n));
            }
            $this->update($object);
        }
        return array();
    }

    public function deleteRows($object_id, $row_ids)
    {
        $object = $this->get($object_id);
        if ($object != null) {
            $ids = array();
            if (!is_array($row_ids)) {
                $ids = explode(",", $row_ids);
            } else {
                $ids = $row_ids;
            }
            foreach ($ids as $id) {
                $this->dbDataDao->removeRow($object->getName(), $id);
            }
            $this->update($object);
        }
        return array();
    }

    public function truncate($object_id)
    {
        $object = $this->get($object_id);
        if ($object != null) {
            $this->update($object);
            return $this->dbDataDao->truncate($object->getName());
        } else {
            return array();
        }
    }

    public function deleteAll($object_id)
    {
        $object = $this->get($object_id);
        if ($object != null) {
            $this->update($object);
            return $this->dbDataDao->deleteAll($object->getName());
        } else {
            return array();
        }
    }

    public function saveColumn($object_id, $column_name, $new_name, $new_type, $new_length = "", $nullable = false)
    {
        $object = $this->get($object_id);
        if ($object != null) {
            $this->update($object);
            return $this->dbStructureService->saveColumn($object->getName(), $column_name, $new_name, $new_type, $new_length, $nullable);
        } else {
            return array();
        }
    }

    public function insertRow($object_id)
    {
        $object = $this->get($object_id);
        if ($object != null) {
            $this->update($object);
            return $this->dbDataDao->addBlankRow($object->getName());
        } else {
            return array();
        }
    }

    public function updateRow($object_id, $row_id, $values, $prefixed = false)
    {
        $object = $this->get($object_id);
        if ($object != null) {
            if ($prefixed) {
                foreach ($values as $k => $v) {
                    $values[substr($k, 4)] = $v;
                    unset($values[$k]);
                }
            }
            $this->update($object);
            return $this->dbDataDao->updateRow($object->getName(), $row_id, $values);
        } else {
            return array();
        }
    }

    public function importFromCsv($object_id, $file_name, $restructure, $header, $delimiter, $enclosure)
    {

        $table = $this->get($object_id);
        if ($table == null) {
            return array();
        }
        if ($restructure) {
            $this->dbDataDao->truncate($table->getName());
        }
        $currentColumns = $this->dbStructureService->getColumns($table->getName());
        if ($restructure) {
            foreach ($currentColumns as $col) {
                if ($col["name"] !== "id") {
                    $this->dbStructureService->removeColumn($table->getName(), $col["name"]);
                }
            }
        } else if (count($currentColumns) == 0) {
            return array();
        }

        $row = 1;
        $colNames = array();
        $colNamesMapping = array();
        $batch = null;
        if (($fp = fopen($file_name, "r")) !== FALSE) {
            while (($data = fgetcsv($fp, 0, $delimiter, $enclosure, "\0")) !== FALSE) {
                $num = count($data);
                if ($row === 1) {
                    if ($header) {
                        for ($c = 0; $c < $num; $c++) {
                            if (!$restructure) {
                                foreach ($currentColumns as $col) {
                                    if ($col["name"] == $data[$c]) {
                                        array_push($colNames, $data[$c]);
                                        array_push($colNamesMapping, $c);
                                    }
                                }
                            } else {
                                array_push($colNames, $data[$c]);
                                array_push($colNamesMapping, $c);
                                if ($data[$c] !== "id") {
                                    $this->dbStructureService->saveColumn($table->getName(), "0", $data[$c], "text");
                                }
                            }
                        }
                        $row++;
                        continue;
                    } else {
                        for ($c = 0; $c < $num; $c++) {
                            if (!$restructure) {
                                if ($c >= count($currentColumns))
                                    break;
                                array_push($colNames, $currentColumns[$c]["name"]);
                                array_push($colNamesMapping, $c);
                            } else {
                                array_push($colNames, "c" . ($c + 1));
                                array_push($colNamesMapping, $c);
                                $this->dbStructureService->saveColumn($table->getName(), "0", "c" . ($c + 1), "text");
                            }
                        }
                    }
                }
                $values = array();
                for ($i = 0; $i < count($colNames); $i++) {
                    $col = $colNames[$i];
                    $values[$col] = $data[$colNamesMapping[$i]];
                }
                $batch = $this->dbDataDao->addInsertBatch($table->getName(), $values, $batch);
                $row++;
            }
        }
        $this->dbDataDao->flushInsertBatch($batch);
        $this->update($table);
        return array();
    }

    public function getAll()
    {
        $result = parent::getAll();
        return $this->assignColumnCollection($result);
    }

    public function getRepository()
    {
        return $this->repository;
    }

    public function assignColumnCollection($tableCollection)
    {
        foreach ($tableCollection as $table) {
            $table->setColumns($this->dbStructureService->getColumns($table->getName()));
        }
        return $tableCollection;
    }

    private static function prefixData(&$data)
    {
        for ($i = 0; $i < count($data); $i++) {
            foreach ($data[$i] as $k => $v) {
                $data[$i]["col_" . $k] = $v;
                unset($data[$i][$k]);
            }
        }
    }

    public function importFromArray($instructions, $obj, &$map, &$renames, &$queue, $secure = true)
    {
        $pre_queue = array();
        if (!isset($renames["DataTable"]))
            $renames["DataTable"] = array();
        if (!isset($map["DataTable"]))
            $map["DataTable"] = array();
        if (isset($map["DataTable"]["id" . $obj["id"]]))
            return array("errors" => null, "entity" => $map["DataTable"]["id" . $obj["id"]]);
        if (count($pre_queue) > 0)
            return array("pre_queue" => $pre_queue);

        $instruction = self::getObjectImportInstruction($obj, $instructions);
        $old_name = $instruction["existing_object_name"];
        $new_name = $this->getNextValidName($this->formatImportName($instruction["rename"], $obj), $instruction["action"], $old_name);
        if ($instruction["action"] != 2 && $old_name != $new_name) {
            $renames["DataTable"][$old_name] = $new_name;
        }

        $result = array();
        $src_ent = $this->findConversionSource($obj, $map);
        if ($instruction["action"] == 1 && $src_ent) {
            $result = $this->importConvert($new_name, $src_ent, $obj, $map, $queue);
            if (isset($instruction["clean"]) && $instruction["clean"] == 1) $this->cleanConvert($result["entity"], $obj);
        } else if ($instruction["action"] == 2 && $src_ent) {
            $map["DataTable"]["id" . $obj["id"]] = $src_ent;
            $result = array("errors" => null, "entity" => $src_ent);
        } else
            $result = $this->importNew($new_name, $obj, $map, $queue);

        if ($result["errors"] !== null && count($result["errors"]) > 0) return $result;

        if ($instruction["action"] != 2 && isset($instruction["data"]) && $instruction["data"] == 2) {
            $this->dbDataDao->truncate($new_name);
            if (isset($obj["data"])) {
                $batch = null;
                foreach ($obj["data"] as $row) {
                    $batch = $this->dbDataDao->addInsertBatch($new_name, $row, $batch);
                }
                $this->dbDataDao->flushInsertBatch($batch);
            }
        }

        return $result;
    }

    private function cleanConvert(DataTable $entity, $importArray)
    {
        foreach ($entity->getColumns() as $currentColumn) {
            $found = false;
            foreach ($importArray["columns"] as $importColumn) {
                if ($currentColumn["name"] == $importColumn["name"]) {
                    $found = true;
                    break;
                }
            }
            if (!$found) $this->dbStructureService->removeColumn($entity->getName(), $currentColumn["name"]);
        }
    }

    protected function importNew($new_name, $obj, &$map, &$queue)
    {
        $user = null;
        $token = $this->securityTokenStorage->getToken();
        if ($token !== null) $user = $token->getUser();

        $starter_content = $obj["name"] == $new_name ? $obj["starterContent"] : false;

        $ent = new DataTable();
        $ent->setName($new_name);
        $ent->setDescription($obj["description"]);
        $ent->setOwner($user);
        $ent->setStarterContent($starter_content);
        $ent->setAccessibility($obj["accessibility"]);
        $ent_errors = $this->validator->validate($ent);
        $ent_errors_msg = array();
        foreach ($ent_errors as $err) {
            array_push($ent_errors_msg, $err->getMessage());
        }
        if (count($ent_errors_msg) > 0)
            return array("errors" => $ent_errors_msg, "entity" => null, "source" => $obj);

        $db_errors = $this->dbStructureService->createTable($new_name, $obj["columns"], array());
        if (count($db_errors) > 0)
            return array("errors" => $db_errors, "entity" => null, "source" => $obj);

        $this->update($ent);
        $map["DataTable"]["id" . $obj["id"]] = $ent;

        return array("errors" => null, "entity" => $ent);
    }

    protected function findConversionSource($obj, $map)
    {
        return $this->get($obj["name"]);
    }

    protected function importConvert($new_name, $src_ent, $obj, &$map, &$queue)
    {
        $user = null;
        $token = $this->securityTokenStorage->getToken();
        if ($token !== null) $user = $token->getUser();

        $old_ent = clone $src_ent;
        $ent = $src_ent;
        $ent->setName($new_name);
        $ent->setDescription($obj["description"]);
        $ent->setOwner($user);
        $ent->setStarterContent($obj["starterContent"]);
        $ent->setAccessibility($obj["accessibility"]);
        $ent_errors = $this->validator->validate($ent);
        $ent_errors_msg = array();
        foreach ($ent_errors as $err) {
            array_push($ent_errors_msg, $err->getMessage());
        }
        if (count($ent_errors_msg) > 0)
            return array("errors" => $ent_errors_msg, "entity" => null, "source" => $obj);

        if ($old_ent->getName() != $new_name) {
            $db_errors = $this->dbStructureService->renameTable($old_ent->getName(), $new_name);
            if (count($db_errors) > 0)
                return array("errors" => $db_errors, "entity" => null, "source" => $obj);
        }

        $old_columns = $ent->getColumns();
        $new_columns = $obj["columns"];
        foreach ($new_columns as $new_col) {
            if ($new_col["name"] == "id") continue;
            $found = false;
            $lengthString = "";
            if (isset($new_col["length"])) $lengthString = $new_col["length"];
            foreach ($old_columns as $old_col) {
                if ($old_col["name"] == $new_col["name"]) {
                    $found = true;
                    $db_errors = $this->dbStructureService->saveColumn($new_name, $old_col["name"], $new_col["name"], $new_col["type"], $lengthString, $new_col["nullable"]);
                    if (count($db_errors) > 0)
                        return array("errors" => $db_errors, "entity" => null, "source" => $obj);
                    break;
                }
            }
            if (!$found) {
                $db_errors = $this->dbStructureService->saveColumn($new_name, "0", $new_col["name"], $new_col["type"], $lengthString, $new_col["nullable"]);
                if (count($db_errors) > 0)
                    return array("errors" => $db_errors, "entity" => null, "source" => $obj);
            }
        }

        $this->update($ent);
        $map["DataTable"]["id" . $obj["id"]] = $ent;

        return array("errors" => null, "entity" => $ent);
    }
}
