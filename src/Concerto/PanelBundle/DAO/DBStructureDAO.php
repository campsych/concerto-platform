<?php

namespace Concerto\PanelBundle\DAO;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Schema\Column;
use Psr\Log\LoggerInterface;

class DBStructureDAO
{
    const SQLCODE_COLUMN_CANNOT_BE_CAST_AUTOMATICALLY = '42804';

    // keys are doctrine types, rather than SQL or PHP ones
    private static $type_defaults = array(
        'smallint' => 0,
        'bigint' => 0,
        'integer' => 0,
        'boolean' => 0,
        'decimal' => 0,
        'float' => 0,
        'date' => "20000101",
        'datetime' => "20000101",
        'text' => "",
        'string' => ""
    );

    private $connection;
    private $logger;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function tableExists($table_name)
    {
        return $this->connection->getSchemaManager()->tablesExist(array($table_name));
    }

    public function columnExists($table_name, $column_name)
    {
        foreach ($this->connection->getSchemaManager()->listTableColumns($table_name) as $col) {
            if ($col->getName() == $column_name) return true;
        }
        return false;
    }

    public function createTable($table_name, $structure, $data)
    {
        $schema = new Schema();
        $table = $schema->createTable($table_name);
        $table->addColumn("id", "bigint", array('autoincrement' => true));
        $table->setPrimaryKey(array("id"));

        foreach ($structure as $col) {
            if ($col["name"] == "id") {
                continue;
            }
            $options = array();
            if ($col["type"] == "string") {
                $options["length"] = 1024;
            }
            if (array_key_exists($col["type"], self::$type_defaults)) {
                $options["default"] = self::$type_defaults[$col["type"]];
            }
            $table->addColumn($col["name"], $col["type"], $options);
        }

        $sql = $schema->toSql($this->connection->getDatabasePlatform());
        foreach ($sql as $query) {
            $this->connection->executeQuery($query);
        }
        foreach ($data as $row) {
            $esc_row = array();
            foreach ($row as $k => $v) {
                $esc_row[$k] = $v;
            }
            $this->connection->insert($table_name, $esc_row);
        }
    }

    public function getColumns($table_name)
    {
        return $this->connection->getSchemaManager()->listTableColumns($table_name);
    }

    public function deleteColumn($table_name, $column_name)
    {
        $tableDiff = new TableDiff($table_name);
        foreach ($this->connection->getSchemaManager()->listTableColumns($table_name) as $col) {
            if ($col->getName() == $column_name) {
                $tableDiff->removedColumns = array($col);
                break;
            }
        }
        $this->connection->getSchemaManager()->alterTable($tableDiff);
    }

    public function saveColumn($table_name, $column_name, $name, $type)
    {
        $options = array();
        if ($type == "string") {
            $options["length"] = 1024;
        }
        if (array_key_exists($type, self::$type_defaults)) {
            $options["default"] = self::$type_defaults[$type];
        }

        $tableDiff = new TableDiff($table_name);
        $newColumn = new Column($name, Type::getType($type), $options);
        if ($column_name === "0") {
            $tableDiff->addedColumns = array($newColumn);
        } else {
            foreach ($this->connection->getSchemaManager()->listTableColumns($table_name) as $col) {
                if ($col->getName() == $column_name) {
                    $columnDiff = new ColumnDiff($column_name, $newColumn);
                    $tableDiff->changedColumns = array($columnDiff);
                    break;
                }
            }
        }
        $this->connection->getSchemaManager()->alterTable($tableDiff);
    }

    public function renameTable($table_old_name, $table_new_name)
    {
        $data = $this->connection->createQueryBuilder()->select("*")->from($table_old_name, "d")->execute();

        $cols = array();
        foreach ($this->getColumns($table_old_name) as $col) {
            array_push($cols, array("name" => $col->getName(), "type" => $col->getType()->getName(), "nullable" => !$col->getNotnull()));
        }

        $this->createTable($table_new_name, $cols, $data);
        $this->deleteTable($table_old_name);
    }

    public function deleteTable($table_name)
    {
        $this->connection->getSchemaManager()->dropTable($table_name);
    }

    public function getTableNames()
    {
        return $this->connection->getSchemaManager()->listTableNames();
    }

}
