<?php

namespace Concerto\APIBundle\Tests\Controller; 

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AFunctionTest extends WebTestCase {

    protected static $entityManager;
    protected static $accessToken;

    public static function setUpBeforeClass() {
        $client = static::createClient();

        self::$entityManager = $client->getContainer()->get("doctrine")->getManager();
    }
    
    protected function setUp() {
        parent::setUp();
       
    }

    public static function truncateClass($class) {
        $cmd = self::$entityManager->getClassMetadata($class);
        $connection = self::$entityManager->getConnection();
        $dbPlatform = $connection->getDatabasePlatform();
        $connection->beginTransaction();
        
        if ( $dbPlatform instanceof PostgreSqlPlatform )
            $connection->query('ALTER SEQUENCE '.$cmd->getTableName().'_id_seq RESTART');
        
        if ( $dbPlatform instanceof MySqlPlatform )
            $connection->query('SET FOREIGN_KEY_CHECKS=0');
        
        $q = $dbPlatform->getTruncateTableSql($cmd->getTableName());
        
        if ( $dbPlatform instanceof PostgreSqlPlatform )
            $q.= ' CASCADE';
        
        $connection->executeUpdate($q);
        
        if ( $dbPlatform instanceof MySqlPlatform )
            $connection->query('SET FOREIGN_KEY_CHECKS=1');
            
        $connection->commit();
    }

}
