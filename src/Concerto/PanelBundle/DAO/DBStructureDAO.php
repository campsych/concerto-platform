<?php

namespace Concerto\PanelBundle\DAO;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;

class DBStructureDAO {

    const SQLCODE_COLUMN_CANNOT_BE_CAST_AUTOMATICALLY = '42804';

    // keys are doctrine types, rather than SQL or PHP ones
    private static $type_defaults = array(
        'bigint' => 0,
        'integer' => 0,
        'text' => '',
        'string' => ''
    );
    private $connection;

    public function __construct(Connection $connection) {
        $this->connection = $connection;
    }

    public function tableExists($table_name) {
        return $this->connection->getSchemaManager()->tablesExist(array($table_name));
    }

    public function columnExists($table_name, $column_name) {
        return $this->connection->getSchemaManager()->createSchema()->getTable($table_name)->hasColumn($column_name);
    }

    public function createTable($table_name, $structure, $data) {
        $schema = new Schema();
        $table = $schema->createTable($table_name);
        $table->addColumn("id", "bigint", array('autoincrement' => true));
        $table->setPrimaryKey(array("id"));

        foreach ($structure as $col) {
            if ($col["name"] == "id") {
                continue;
            }
            $column_definition = $table->addColumn($col["name"], $col["type"]);

            if (!$this->connection->getDatabasePlatform() instanceof MySqlPlatform) {
                $column_definition->setDefault(self::$type_defaults[Type::getType($col["type"])->getName()]);
            }
        }

        $sql = $schema->getMigrateFromSql(new Schema(), $this->connection->getDatabasePlatform());
        foreach ($sql as $query) {
            $this->connection->executeQuery($query);
        }
        foreach ($data as $row) {
            $esc_row = array();
            foreach ($row as $k => $v) {
                $esc_row["`" . $k . "`"] = $v;
            }
            $this->connection->insert($table_name, $esc_row);
        }
    }

    public function getColumns($table_name) {
        return $this->connection->getSchemaManager()->listTableColumns($table_name);
    }

    public function deleteColumn($table_name, $column_name) {

        $fromSchema = $this->connection->getSchemaManager()->createSchema();
        $toSchema = clone $fromSchema;
        $toSchema->getTable($table_name)->dropColumn($column_name);

        $sql = $fromSchema->getMigrateToSql($toSchema, $this->connection->getDatabasePlatform());
        foreach ($sql as $query) {
            $this->connection->executeQuery($query);
        }
    }

    public function saveColumn($table_name, $column_name, $name, $type) {
        $fromSchema = $this->connection->getSchemaManager()->createSchema();
        $toSchema = clone $fromSchema;

        if ($column_name === "0") {
            $column_definition = $toSchema->getTable($table_name)->addColumn($name, $type);
        } else {
            $column_definition = $toSchema->getTable($table_name)->getColumn($column_name)->setLength(null)->setType(Type::getType($type));
        }

        if (!$this->connection->getDatabasePlatform() instanceof MySqlPlatform) {
            $column_definition->setDefault(self::$type_defaults[Type::getType($type)->getName()]);
        }

        $sql = $fromSchema->getMigrateToSql($toSchema, $this->connection->getDatabasePlatform());

        try {
            foreach ($sql as $query) {
                $this->connection->executeQuery($query);
            }
        }
        // handle specific case of uncastastable column type (like VARCHAR -> INTEGER)
        catch (DBALException $exc) {
            if (strpos($exc->getMessage(), self::SQLCODE_COLUMN_CANNOT_BE_CAST_AUTOMATICALLY) &&
                    ( $this->connection->getDatabasePlatform() instanceof PostgreSqlPlatform )) {
                throw new DAOUnsupportedOperationException(
                "This column type change operation is not supported on PostgreSQL backends.", self::SQLCODE_COLUMN_CANNOT_BE_CAST_AUTOMATICALLY, $exc
                );
            } else
                throw $exc;
        }

        if ($column_name !== "0" && $column_name !== $name) {
            $tableDiff = new TableDiff($table_name);
            $tableDiff->renamedColumns[$column_name] = new Column($name, Type::getType($type));
            $this->connection->getSchemaManager()->alterTable($tableDiff);
        }
    }

    public function renameTable($table_old_name, $table_new_name) {
        $data = $this->connection->createQueryBuilder()->select("*")->from("`" . $table_old_name . "`", "d")->execute();

        $cols = array();
        foreach ($this->getColumns($table_old_name) as $col) {
            array_push($cols, array("name" => $col->getName(), "type" => $col->getType()->getName(), "nullable" => !$col->getNotnull()));
        }

        $this->createTable($table_new_name, $cols, $data);
        $this->deleteTable($table_old_name);
    }

    public function deleteTable($table_name) {
        $fromSchema = $this->connection->getSchemaManager()->createSchema();
        $toSchema = clone $fromSchema;
        $toSchema->dropTable($table_name);

        $sql = $fromSchema->getMigrateToSql($toSchema, $this->connection->getDatabasePlatform());
        foreach ($sql as $query) {
            $this->connection->executeQuery($query);
        }
    }

    public function getTableNames() {
        return $this->connection->getSchemaManager()->listTableNames();
    }

}
