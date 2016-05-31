<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\DAO\DBDataDAO;
use Symfony\Component\Validator\Validator\RecursiveValidator;
use Concerto\PanelBundle\Entity\DataTable;
use Concerto\PanelBundle\Repository\DataTableRepository;
use Concerto\PanelBundle\Entity\User;
use Concerto\PanelBundle\Entity\AEntity;
use Concerto\PanelBundle\Security\ObjectVoter;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class DataTableService extends AExportableSectionService {

    private $dbStructureService;
    private $dbDataDao;

    public function __construct(DataTableRepository $repository, RecursiveValidator $validator, DBStructureService $dbStructureService, DBDataDAO $dbDataDao, AuthorizationChecker $securityAuthorizationChecker) {
        parent::__construct($repository, $validator, $securityAuthorizationChecker);

        $this->dbStructureService = $dbStructureService;
        $this->dbDataDao = $dbDataDao;
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
            $object = new DataTable();
        }
        if ($object !== null)
            $this->assignColumnCollection(array($object));

        return $object;
    }

    public function save(User $user, $object_id, $name, $description, $accessibility, $protected, $archived, $owner, $groups) {
        $errors = array();
        $object = $this->get($object_id);
        $new = false;
        if ($object !== null) {
            $old_name = $object->getName();
        } else {
            $new = true;
            $object = new DataTable();
            $object->setOwner($user);
        }
        $object->setUpdated();
        $object->setUpdatedBy($user);
        $object->setName($name);
        if ($description !== null) {
            $object->setDescription($description);
        }
        if (!$new && $object->isProtected() == $protected && $protected) {
            array_push($errors, "validate.protected.mod");
        }

        if ($this->securityAuthorizationChecker->isGranted(User::ROLE_SUPER_ADMIN)) {
            $object->setAccessibility($accessibility);
            $object->setOwner($owner);
            $object->setGroups($groups);
        }

        $object->setProtected($protected);
        $object->setArchived($archived);
        foreach ($this->validator->validate($object) as $err) {
            array_push($errors, $err->getMessage());
        }
        if (count($errors) > 0) {
            return array("object" => null, "errors" => $errors);
        }

        if ($new) {
            $errors = $this->dbStructureService->createDefaultTable($name);
        } else if ($old_name != $name) {
            $errors = $this->dbStructureService->renameTable($old_name, $name);
        }
        if (count($errors) > 0) {
            return array("object" => null, "errors" => $errors);
        }

        $this->repository->save($object);
        return array("object" => $object, "errors" => $errors);
    }

    public function delete($object_ids, $secure = true) {
        $object_ids = explode(",", $object_ids);

        $result = array();
        foreach ($object_ids as $object_id) {
            $object = $this->get($object_id, false, $secure);
            if (!$object)
                continue;
            if ($object->isProtected() && $secure) {
                array_push($result, array("object" => $object, "errors" => array("validate.protected.mod")));
                continue;
            }
            if ($object != null) {
                $this->dbStructureService->removeTable($object->getName());
            }
            $this->repository->delete($object);
            array_push($result, array("object" => $object, "errors" => array()));
        }
        return $result;
    }

    public function getColumns($object_id) {
        $object = $this->get($object_id);
        if ($object != null) {
            return $this->dbStructureService->getColumns($object->getName());
        } else {
            return array();
        }
    }

    public function getColumn($object_id, $column_name) {
        $object = $this->get($object_id);
        if ($object != null) {
            return $this->dbStructureService->getColumn($object->getName(), $column_name);
        } else {
            return array();
        }
    }

    public function getData($object_id, $prefixed = false, $row_id = null, $filter = null, $operators = null) {
        $object = $this->get($object_id);
        if ($object != null) {
            $data = $this->dbDataDao->getData($object->getName(), $row_id, $filter, $operators);
            if ($prefixed) {
                self::prefixData($data);
            }
            return $data;
        } else {
            return array();
        }
    }

    public function getFilteredData($object_id, $prefixed = false, $filters = null) {
        $object = $this->get($object_id);
        if ($object != null) {
            $data = $this->dbDataDao->fetchMatchingData($object->getName(), $filters);
            if ($prefixed) {
                self::prefixData($data);
            }
            return $data;
        } else {
            return array();
        }
    }

    public function countFilteredData($object_id, $filters) {
        $object = $this->get($object_id);
        if ($object != null) {
            return $this->dbDataDao->countMatchingData($object->getName(), $filters);
        } else {
            return 0;
        }
    }

    public function streamJsonData($object_id, $prefixed = false) {
        $object = $this->get($object_id);
        if ($object != null) {
            $result = $this->dbDataDao->getStreamDataResult($object->getName());
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
                flush();
                $j++;
            }
            echo "]";
        }
    }

    public function deleteColumns($object_id, $column_names) {
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
        }
        return array();
    }

    public function deleteRows($object_id, $row_ids) {
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
        }
        return array();
    }

    public function truncate($object_id) {
        $object = $this->get($object_id);
        if ($object != null) {
            $this->dbDataDao->truncate($object->getName());
        } else {
            return array();
        }
    }

    public function saveColumn($object_id, $column_name, $new_name, $new_type) {
        $object = $this->get($object_id);
        if ($object != null) {
            return $this->dbStructureService->saveColumn($object->getName(), $column_name, $new_name, $new_type);
        } else {
            return array();
        }
    }

    public function insertRow($object_id) {
        $object = $this->get($object_id);
        if ($object != null) {
            return $this->dbDataDao->addBlankRow($object->getName());
        } else {
            return array();
        }
    }

    public function updateRow($object_id, $row_id, $values, $prefixed = false) {
        $object = $this->get($object_id);
        if ($object != null) {
            if ($prefixed) {
                foreach ($values as $k => $v) {
                    $values[substr($k, 4)] = $v;
                    unset($values[$k]);
                }
            }
            return $this->dbDataDao->updateRow($object->getName(), $row_id, $values);
        } else {
            return array();
        }
    }

    public function importFromCsv($object_id, $file_name, $restructure, $header, $delimiter, $enclosure) {

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
            while (($data = fgetcsv($fp, 0, $delimiter, $enclosure)) !== FALSE) {
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
                    $values["`" . $col . "`"] = $data[$colNamesMapping[$i]];
                }
                $batch = $this->dbDataDao->addInsertBatch($table->getName(), $values, $batch);
                $row++;
            }
        }
        $this->dbDataDao->flushInsertBatch($batch);
        return array();
    }

    public function getAll() {
        $result = parent::getAll();
        return $this->assignColumnCollection($result);
    }

    public function getRepository() {
        return $this->repository;
    }

    public function assignColumnCollection($tableCollection) {
        foreach ($tableCollection as $table) {
            $table->setColumns($this->dbStructureService->getColumns($table->getName()));
        }
        return $tableCollection;
    }

    private static function prefixData(&$data) {
        for ($i = 0; $i < count($data); $i++) {
            foreach ($data[$i] as $k => $v) {
                $data[$i]["col_" . $k] = $v;
                unset($data[$i][$k]);
            }
        }
    }

    public function entityToArray(AEntity $entity) {
        $e = $entity->jsonSerialize();
        $e["data"] = $this->dbDataDao->getData($entity->getName());
        return $e;
    }

    public function importFromArray(User $user, $newName, $obj, &$map, &$queue) {
        $pre_queue = array();
        if (array_key_exists("DataTable", $map) && array_key_exists("id" . $obj["id"], $map["DataTable"])) {
            return(array());
        }

        $formattedName = $this->formatImportName($user, $newName, $obj);
        
        $db_errors = $this->dbStructureService->createTable($formattedName, $obj["columns"], $obj["data"]);
        if (count($db_errors) > 0) {
            return array("errors" => $db_errors, "entity" => null, "source" => $obj);
        }
        
        if (count($pre_queue) > 0) {
            return array("pre_queue" => $pre_queue);
        }

        $ent = new DataTable();
        $ent->setName($formattedName);
        $ent->setDescription($obj["description"]);
        $ent->setGlobalId($obj["globalId"]);
        $ent->setOwner($user);
        $ent->setProtected($obj["protected"] == "1");
        $ent->setRevision($obj["revision"]);
        $ent->setChecksum($obj["checksum"]);
        $ent->setStarterContent($obj["starterContent"]);
        $ent_errors = $this->validator->validate($ent);
        $ent_errors_msg = array();
        foreach ($ent_errors as $err) {
            array_push($ent_errors_msg, $err->getMessage());
        }
        if (count($ent_errors_msg) > 0) {
            return array("errors" => $ent_errors_msg, "entity" => null, "source" => $obj);
        }
        $this->repository->save($ent);

        if (!array_key_exists("DataTable", $map)) {
            $map["DataTable"] = array();
        }
        $map["DataTable"]["id" . $obj["id"]] = $ent->getId();

        return array("errors" => null, "entity" => $ent);
    }

}
