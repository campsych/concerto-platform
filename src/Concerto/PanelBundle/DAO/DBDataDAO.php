<?php

namespace Concerto\PanelBundle\DAO;

use Doctrine\DBAL\Connection;

class DBDataDAO
{

    public $connection;

    public function __construct(Connection $con)
    {
        $this->connection = $con;
    }

    public function getData($table_name, $id = null, $filter = null)
    {
        $stmt = $this->getFilteredDataResult($table_name, $id, $filter);
        return $stmt->fetchAll();
    }

    public function getFilteredDataResult($table_name, $id = null, $filter = null)
    {
        $this->connection->getWrappedConnection()->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        $q = $this->connection->createQueryBuilder()->select("*")->from($table_name, "d");

        $i = 0;
        if ($filter !== null) {
            foreach ($filter as $f) {

                if (is_a($f["value"], "DateTime"))
                    $f["value"] = $f["value"]->format("Y-m-d");
                if ($i == 0) {
                    $q = $q->where("d." . $f["name"] . " " . $f["op"] . " :p$i")->setParameter(":p$i", $f["value"]);
                } else {
                    $q = $q->andWhere("d." . $f["name"] . " " . $f["op"] . " :p$i")->setParameter(":p$i", $f["value"]);
                }
                $i++;
            }
        }

        if ($id) {
            if ($i == 0)
                $q = $q->where("d.id = :id");
            else
                $q = $q->andWhere("d.id = :id");
            $q = $q->setParameter(":id", $id);
        }
        return $q->execute();
    }

    /**
     * @param $table_name
     * @param $filters array($k => "name", $v => "value")
     * @return array
     */
    public function fetchMatchingData($table_name, $filters)
    {
        $builder = $this->connection->createQueryBuilder()->select("*")->from($table_name, "d");

        if (!$filters) {
            return $builder->execute()->fetchAll();
        }
        $f = json_decode($filters, true);

        foreach ($f["filters"] as $k => $v) {
            if (!$v) continue;
            $builder->andWhere("d.$k LIKE :filter")->setParameter('filter', '%' . $v . '%');
        }
        foreach ($f["sorting"] as $sort) {
            $builder->addOrderBy("d." . $sort["name"], $sort["dir"]);
        }

        return $builder->setFirstResult(($f["paging"]["page"] - 1) * $f["paging"]["pageSize"])->setMaxResults($f["paging"]["pageSize"])->execute()->fetchAll();
    }

    public function countMatchingData($table_name, $filters)
    {
        $builder = $this->connection->createQueryBuilder()->select('count(d.id)')->from($table_name, "d");

        if ($filters) {
            $f = json_decode($filters, true);

            foreach ($f["filters"] as $k => $v) {
                if (!$v) continue;
                $builder->andWhere("d.$k LIKE :filter")->setParameter('filter', '%' . $v . '%');
            }
        }

        return (int)$builder->execute()->fetchColumn(0);
    }

    public function updateRow($table_name, $row_id, $values, $id_field = "id")
    {
        $qb = $this->connection->createQueryBuilder()->update($table_name);
        $i = -1;
        $cols = $this->connection->getSchemaManager()->listTableColumns($table_name);
        foreach ($values as $k => $v) {
            $i++;
            if ($k == "id") continue;
            $found = false;
            foreach ($cols as $col) {
                if ($col->getName() == $k) {
                    $found = true;
                    break;
                }
            }
            if (!$found) continue;

            $qb->set($k, ":k" . $i)->setParameter(":k" . $i, $v);
        }
        $qb->where("$id_field=:id")->setParameter(":id", $row_id)->execute();
        return array();
    }

    public function removeRow($table_name, $id, $id_field = "id")
    {
        $this->connection->delete($table_name, array("$id_field" => $id));
        return array();
    }

    public function addBlankRow($table_name)
    {
        $driver = $this->connection->getDriver()->getName();
        switch ($driver) {
            case 'pdo_pgsql':
                {
                    $this->connection->query('INSERT INTO ' . $table_name . ' ( id )  VALUES ( DEFAULT )');
                    break;
                }
            case 'pdo_sqlsrv':
                {
                    $this->connection->query('INSERT INTO ' . $table_name . ' DEFAULT VALUES ');
                    break;
                }
            default:
                $this->connection->insert($table_name, array());
                break;
        }

        return array();
    }

    public function insertRow($table_name, $values)
    {
        $this->connection->insert($table_name, $values);
        return $this->getLastInsertId();
    }

    public function getLastInsertId()
    {
        return $this->connection->lastInsertId();
    }

    public function addInsertBatch($table_name, $data, $batch = null)
    {
        //TODO check sqlite and sql server

        if ($this->connection->getDriver()->getName() == "pdo_sqlsrv") {
            $this->connection->prepare("SET IDENTITY_INSERT $table_name ON")->execute();
        }

        if ($this->connection->getDriver()->getName() !== "pdo_mysql") {
            $this->insertRow($table_name, $data);
        } else {
            if ($batch === null) {
                $batch = array(
                    "insert_template" => 'INSERT INTO ' . $table_name . ' (' . implode(', ', array_keys($data)) . ')  VALUES ',
                    "values_template" => '(' . implode(', ', array_fill(0, count($data), '?')) . ')',
                    "index" => 0,
                    "sql" => "",
                    "params" => array()
                );
            }
            if ($batch["index"] == 0) {
                $batch["sql"] .= $batch["insert_template"];
            } else {
                $batch["sql"] .= ",";
            }
            $batch["sql"] .= $batch["values_template"];
            $batch["params"] = array_merge($batch["params"], array_values($data));
            $batch["index"]++;
            if ($batch["index"] % 50 == 0) {
                $batch = $this->flushInsertBatch($batch);
            }
        }
        return $batch;
    }

    public function flushInsertBatch($batch)
    {
        if ($this->connection->getDriver()->getName() === "pdo_mysql") {
            if ($batch !== null && $batch["index"] > 0) {
                $this->connection->connect();
                $this->connection->executeUpdate($batch["sql"], $batch["params"]);
                $batch["sql"] = "";
                $batch["params"] = array();
                $batch["index"] = 0;
            }
        }
        return $batch;
    }

    public function truncate($table_name)
    {
        $dbPlatform = $this->connection->getDatabasePlatform();
        if ($this->connection->getDriver()->getName() === "pdo_mysql")
            $this->connection->query('SET FOREIGN_KEY_CHECKS=0');

        $q = $dbPlatform->getTruncateTableSql($table_name);
        $this->connection->executeUpdate($q);

        if ($this->connection->getDriver()->getName() === "pdo_mysql")
            $this->connection->query('SET FOREIGN_KEY_CHECKS=1');

        return array();
    }

}
