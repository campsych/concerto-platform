<?php

namespace Concerto\PanelBundle\EventSubscriber;

use Doctrine\DBAL\Event\ConnectionEventArgs;

class OracleSubscriber
{
    public function postConnect(ConnectionEventArgs $args)
    {
        $conn = $args->getConnection();

        if ($conn->getDriver()->getName() == "oci8") {
            $conn->executeQuery(
                "ALTER SESSION SET NLS_DATE_FORMAT='YYYY-MM-DD HH24:MI:SS'"
            );
            $conn->executeQuery(
                "ALTER SESSION SET NLS_TIMESTAMP_FORMAT='YYYY-MM-DD HH24:MI:SS'"
            );
        }
    }
}