<?php

namespace Concerto\APIBundle\Service;

use Concerto\PanelBundle\Entity\DataTable;
use Concerto\PanelBundle\Service\DataTableService;
use Concerto\PanelBundle\DAO\DBDataDAO;
use Concerto\PanelBundle\DAO\DBStructureDAO;
use Symfony\Component\HttpFoundation\Response;

class DataRecordService
{

    private $dataTableService;
    private $dbDataDAO;
    private $dbStructureDAO;

    protected static $excluded_filters = array(
        "access_token",
        "format"
    );

    public function __construct(DataTableService $dataTableService, DBDataDAO $dbDataDAO, DBStructureDAO $dbStructureDAO)
    {
        $this->dataTableService = $dataTableService;
        $this->dbDataDAO = $dbDataDAO;
        $this->dbStructureDAO = $dbStructureDAO;
    }

    public function getData($table_id, $id, $format = null)
    {
        $table = $this->dataTableService->get($table_id, false, false);
        if ($table === null)
            return array("response" => Response::HTTP_NOT_FOUND, "result" => null);

        $result = $this->dbDataDAO->getData($table->getName(), $id);
        if (!count($result))
            return array("response" => Response::HTTP_NOT_FOUND, "result" => null);

        if ($format !== null) {
            return array("response" => Response::HTTP_OK, "result" => $this->serialize($result, $format));
        }
        return array("response" => Response::HTTP_OK, "result" => $result);
    }

    public function getDataCollection($table_id, $filter, $format = null)
    {
        $table = $this->dataTableService->get($table_id, false, false);
        if ($table === null) {
            return array("response" => Response::HTTP_NOT_FOUND, "result" => null);
        }

        foreach (static::$excluded_filters as $key) {
            unset($filter[$key]);
        }

        if (!$this->formatDataFilter($table, $filter)) {
            return array("response" => Response::HTTP_BAD_REQUEST, "result" => null);
        }

        $result = $this->dbDataDAO->getData($table->getName(), null, $filter);
        if ($format !== null) {
            return array("response" => Response::HTTP_OK, "result" => $this->serialize($result, $format));
        }
        return array("response" => Response::HTTP_OK, "result" => $result);
    }

    public function updateData($table_id, $id, $newSerializedData, $format = "json")
    {
        $table = $this->dataTableService->get($table_id, false, false);
        if ($table === null)
            return array("response" => Response::HTTP_NOT_FOUND, "result" => null);

        $newData = $this->deserialize($newSerializedData, "json");

        $columns = $this->dbStructureDAO->getColumns($table->getName());
        foreach ($newData as $k => $v) {
            if (!array_key_exists($k, $columns))
                return array("response" => Response::HTTP_BAD_REQUEST, "result" => null);
        }

        $this->dbDataDAO->updateRow($table->getName(), $id, $newData);
        $result = $this->dbDataDAO->getData($table->getName(), $id);
        if ($format !== null) {
            return array("response" => Response::HTTP_OK, "result" => $this->serialize($result, $format));
        }
        return array("response" => Response::HTTP_OK, "result" => $result);
    }

    public function insertData($table_id, $newSerializedData, $format = "json")
    {
        $table = $this->dataTableService->get($table_id, false, false);
        if ($table === null)
            return array("response" => Response::HTTP_NOT_FOUND, "result" => null);

        $newData = $this->deserialize($newSerializedData, "json");

        $columns = $this->dbStructureDAO->getColumns($table->getName());
        foreach ($newData as $k => $v) {
            if (!array_key_exists($k, $columns))
                return array("response" => Response::HTTP_BAD_REQUEST, "result" => null);
        }

        $lid = $this->dbDataDAO->insertRow($table->getName(), $newData);
        $result = $this->dbDataDAO->getData($table->getName(), $lid);
        if ($format !== null) {
            return array("response" => Response::HTTP_OK, "result" => $this->serialize($result, $format));
        }
        return array("response" => Response::HTTP_OK, "result" => $result);
    }

    public function deleteData($table_id, $id)
    {
        $table = $this->dataTableService->get($table_id, false, false);
        if ($table === null)
            return array("response" => Response::HTTP_NOT_FOUND, "result" => null);

        $this->dbDataDAO->removeRow($table->getName(), $id);
        return array("response" => Response::HTTP_OK, "result" => null);
    }

    public function serialize($data, $format = "json")
    {
        switch ($format) {
            default:
                return json_encode($data);
        }
    }

    public function deserialize($data, $format = "json")
    {
        switch ($format) {
            default:
                return json_decode($data, true);
        }
    }

    protected function formatFilter($filter)
    {
        $formatted_filters = array();
        foreach ($filter as $k => $v) {
            unset($filter[$k]);
            $lc = substr($k, strlen($k) - 1, 1);
            $op = "=";
            if ($lc === ">" || $lc === "<" || $lc === "!") {
                $k = substr($k, 0, strlen($k) - 1);
                $op = $lc . $op;
            }
            array_push($formatted_filters, array(
                "name" => $k,
                "op" => $op,
                "value" => $v
            ));
        }
        return $formatted_filters;
    }

    protected function formatDataFilter(DataTable $table, &$filter)
    {
        $formatted_filters = $this->formatFilter($filter);

        $columns = $this->dbStructureDAO->getColumns($table->getName());
        for ($i = 0; $i < count($formatted_filters); $i++) {
            $f = $formatted_filters[$i];
            $found = false;
            foreach ($columns as $column) {
                if ($column->getName() != $f["name"])
                    continue;
                $found = true;
                switch ($column->getType()->getName()) {
                    case "date":
                        $f["value"] = new \DateTime($f["value"]);
                        break;
                    case "boolean":
                        $f["value"] = strtolower($f["value"]) == "true";
                        break;
                }
                break;
            }
            $formatted_filters[$i] = $f;
            if (!$found)
                return false;
        }
        $filter = $formatted_filters;
        return true;
    }

}
