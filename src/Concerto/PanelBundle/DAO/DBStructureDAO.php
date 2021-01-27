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
    // keys are doctrine types, rather than SQL or PHP ones
    private static $typeDefaultValues = [
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
    ];
    private static $typeDefaultLengths = [
        'string' => 1024 //must leave at at least 1024, otherwise older imports will truncate data
    ];
    private static $typeDefaultPrecisions = [
        'decimal' => 10
    ];
    private static $typeDefaultScales = [
        'decimal' => 2
    ];

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
            $options = [];
            $lengthString = "";
            if (isset($col["length"])) $lengthString = $col["length"];
            if (isset(self::$typeDefaultLengths[$col["type"]])) $options["length"] = self::$typeDefaultLengths[$col["type"]];
            if (isset(self::$typeDefaultPrecisions[$col["type"]])) $options["precision"] = self::$typeDefaultPrecisions[$col["type"]];
            if (isset(self::$typeDefaultScales[$col["type"]])) $options["scale"] = self::$typeDefaultScales[$col["type"]];
            $options["notnull"] = !$col["nullable"];
            if ($col["nullable"]) $options["default"] = null;
            else if (isset(self::$typeDefaultValues[$col["type"]])) $options["default"] = self::$typeDefaultValues[$col["type"]];
            $this->applyLengthStringToColumnOptions($col["type"], $lengthString, $options);

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

    public function saveColumn($table_name, $column_name, $name, $type, $lengthString = "", $nullable = false)
    {
        $options = array();
        if (isset(self::$typeDefaultLengths[$type])) $options["length"] = self::$typeDefaultLengths[$type];
        if (isset(self::$typeDefaultPrecisions[$type])) $options["precision"] = self::$typeDefaultPrecisions[$type];
        if (isset(self::$typeDefaultScales[$type])) $options["scale"] = self::$typeDefaultScales[$type];
        $options["notnull"] = !$nullable;
        if ($nullable) $options["default"] = null;
        else if (isset(self::$typeDefaultValues[$type])) $options["default"] = self::$typeDefaultValues[$type];
        $this->applyLengthStringToColumnOptions($type, $lengthString, $options);

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

    public function getLengthString(Column $column)
    {
        switch ($column->getType()->getName()) {
            case "string":
                return $column->getLength();
            case "decimal":
                return $column->getPrecision() . "," . $column->getScale();
        }
        return "";
    }

    public function applyLengthStringToColumnOptions($type, $string, &$options)
    {
        $string = trim($string);
        switch ($type) {
            case "string":
                if (is_numeric($string)) $options["length"] = $string;
                break;
            case "decimal":
                $elems = explode(",", $string);
                if (count($elems) > 0) {
                    $precision = trim($elems[0]);
                    if (is_numeric($precision)) $options["precision"] = $precision;
                }
                if (count($elems) > 1) {
                    $scale = trim($elems[1]);
                    if (is_numeric($scale)) $options["scale"] = $scale;
                }
                break;
        }
    }
}
