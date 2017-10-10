<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\DAO\DBStructureDAO;
use Psr\Log\LoggerInterface;

class DBStructureService
{

    private $dbStructureDao;
    private $logger;

    public function __construct(DBStructureDAO $dbStructureDao, LoggerInterface $logger)
    {
        $this->dbStructureDao = $dbStructureDao;
        $this->logger = $logger;
    }

    private function validateTable($table_old_name, $table_new_name)
    {
        $errors = array();
        if ($this->dbStructureDao->tableExists($table_new_name) && $table_old_name != $table_new_name) {
            $errors[] = "validate.table.name.unique";
        }
        return $errors;
    }

    private function validateColumn($table_name, $column_old_name, $column_new_name)
    {
        $errors = array();
        if ($column_new_name === "") {
            $errors[] = "validate.table.column.name.blank";
            return $errors;
        }
        if ($this->dbStructureDao->columnExists($table_name, $column_new_name) && $column_old_name != $column_new_name) {
            $errors[] = "validate.table.column.name.unique";
        }
        if (!preg_match("/^[a-zA-Z][a-zA-Z0-9_]*(?<!_)$/", $column_new_name)) {
            $errors[] = "validate.table.column.name.incorrect";
        }
        return $errors;
    }

    public function createDefaultTable($table_name)
    {
        $errors = $this->validateTable(null, $table_name);
        if (count($errors) > 0) {
            return $errors;
        }
        $this->dbStructureDao->createTable($table_name, array(array("name" => "temp", "type" => "text")), array());
        return $errors;
    }

    public function createTable($table_name, $structure, $data)
    {
        $errors = $this->validateTable(null, $table_name);
        if (count($errors) > 0) {
            return $errors;
        }
        $this->dbStructureDao->createTable($table_name, $structure, $data);
        return $errors;
    }

    public function getColumns($table_name)
    {
        $result = array();
        $cols = $this->dbStructureDao->getColumns($table_name);
        foreach ($cols as $col) {
            array_push($result, array("name" => $col->getName(), "type" => $col->getType()->getName(), "nullable" => !$col->getNotnull()));
        }
        return $result;
    }

    public function getColumn($table_name, $column_name)
    {
        $result = array();
        $cols = $this->dbStructureDao->getColumns($table_name);
        foreach ($cols as $col) {
            if ($column_name == $col->getName()) {
                return array("name" => $col->getName(), "type" => $col->getType()->getName(), "nullable" => !$col->getNotnull());
            }
        }
        return $result;
    }

    public function removeColumn($table_name, $column_name)
    {
        $this->dbStructureDao->deleteColumn($table_name, $column_name);
        return array();
    }

    public function saveColumn($table_name, $column_name, $name, $type)
    {
        $errors = $this->validateColumn($table_name, $column_name, $name);
        if (count($errors) > 0) {
            return $errors;
        }
        $this->dbStructureDao->saveColumn($table_name, $column_name, $name, $type);
        return array();
    }

    public function renameTable($table_old_name, $table_new_name)
    {
        $errors = $this->validateTable($table_old_name, $table_new_name);
        if (count($errors) > 0) {
            return $errors;
        }
        $this->dbStructureDao->renameTable($table_old_name, $table_new_name);
        return array();
    }

    public function removeTable($table_name)
    {
        $this->dbStructureDao->deleteTable($table_name);
        return array();
    }

}
