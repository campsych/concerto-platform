<?php

namespace Tests\Concerto\PanelBundle\Controller\FunctionalTests;

use Tests\Concerto\PanelBundle\AFunctionalTest;

class DataTableControllerTest extends AFunctionalTest {

    private static $repository;
    private static $driver_class;

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        self::$repository = static::$entityManager->getRepository("ConcertoPanelBundle:DataTable");
        self::$driver_class = get_class(static::$entityManager->getConnection()->getDatabasePlatform());
    }

    protected function setUp() {
        parent::setUp();

        $this->dropTable("main_table");
        $this->dropTable("main_table_1");
        $this->dropTable("imported_table");
        $this->dropTable("new_table");
        $this->dropTable("edited_table");

        //creating main table
        $client = self::createLoggedClient();
        $client->request("POST", "/admin/DataTable/-1/save", array(
            "name" => "main_table",
            "description" => "table description",
            "accessibility" => 0
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $content["result"]);

        $client->request("POST", "/admin/DataTable/1/row/insert");
        $client->request("POST", "/admin/DataTable/1/row/1/update", array(
            "values" => array("temp" => "temp1")
        ));
        $client->request("POST", "/admin/DataTable/1/row/insert");
        $client->request("POST", "/admin/DataTable/1/row/2/update", array(
            "values" => array("temp" => "temp2")
        ));
    }

    private function dropTable($name) {
        $fromSchema = static::$entityManager->getConnection()->getSchemaManager()->createSchema();
        $toSchema = clone $fromSchema;
        try {
            $toSchema->dropTable($name);

            $sql = $fromSchema->getMigrateToSql($toSchema, static::$entityManager->getConnection()->getDatabasePlatform());
            foreach ($sql as $query) {
                static::$entityManager->getConnection()->executeQuery($query);
            }
        } catch (\Exception $ex) {
            
        }
    }

    public function testCollectionAction() {
        $client = self::createLoggedClient();

        $client->request('POST', '/admin/DataTable/collection');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $expected = array(
            array(
                "class_name" => "DataTable",
                "id" => 1,
                "name" => "main_table",
                "description" => "table description",
                "columns" => array(
                    array(
                        'name' => 'id',
                        'type' => 'bigint',
                        'nullable' => false
                    ),
                    array(
                        'name' => 'temp',
                        'type' => 'text',
                        'nullable' => false
                    )
                ),
                "accessibility" => 0,
                "archived" => "0",
                "starterContent" => false,
                "owner" => null,
                "groups" => "",
                "updatedOn" => json_decode($client->getResponse()->getContent(), true)[0]['updatedOn'],
                "updatedBy" => 'admin'
            )
        );
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testFormActionNew() {
        $client = self::createLoggedClient();
        $crawler = $client->request(
                "GET", "/admin/DataTable/form/add"
        );
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertGreaterThan(0, $crawler->filter("input[type='text'][ng-model='object.name']")->count());
    }

    /**
     * Is this test meaningful anymore?
     */
    public function testFormActionEdit() {
        $client = self::createLoggedClient();

        $crawler = $client->request("POST", "/admin/DataTable/form/edit");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertGreaterThan(0, $crawler->filter("html:contains('Data table structure')")->count());
        $this->assertGreaterThan(0, $crawler->filter("html:contains('Table data')")->count());
        $this->assertGreaterThan(0, $crawler->filter("input[type='text'][ng-model='object.name']")->count());
    }

    public function testDeleteAction() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/1/delete");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array("result" => 0, "object_ids" => "1"), json_decode($client->getResponse()->getContent(), true));
        self::$repository->clear();
        $entity = self::$repository->find(1);
        $this->assertNull($entity);
    }

    /**
     * @dataProvider exportDataProvider
     */
    public function testExportAction($path_suffix, $use_gzip) {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/1/export" . $path_suffix);
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/x-download'));

        $content = json_decode(
                ( $use_gzip ) ? gzuncompress($client->getResponse()->getContent()) : $client->getResponse()->getContent(), true
        );
        $this->assertArrayHasKey("hash", $content["collection"][0]);
        unset($content["collection"][0]["hash"]);

        $this->assertEquals(array(array(
                'class_name' => 'DataTable',
                'id' => 1,
                'name' => 'main_table',
                'description' => 'table description',
                'accessibility' => 0,
                "archived" => "0",
                "starterContent" => false,
                "owner" => null,
                "groups" => "",
                'updatedOn' => $content["collection"][0]["updatedOn"],
                'updatedBy' => 'admin',
                'columns' => array(
                    array('name' => 'id', 'type' => 'bigint', 'nullable' => false),
                    array('name' => 'temp', 'type' => 'text', 'nullable' => false)
                ))), $content["collection"]);
    }

    public function testImportNewAction() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/import", array(
            "file" => "DataTable_1.concerto.json",
            "instructions" => json_encode(array(
                array(
                    "class_name" => "DataTable",
                    "id" => 8,
                    "rename" => "imported_table",
                    "action" => "0",
                    "starter_content" => false,
                    "existing_object_name" => null
                )
            ))
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $new_entity = self::$repository->find(2);
        $this->assertNotNull($new_entity);
        $decoded_response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $decoded_response["result"]);
    }

    public function testImportNewSameNameAction() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/import", array(
            "file" => "DataTable_1.concerto.json",
            "instructions" => json_encode(array(
                array(
                    "class_name" => "DataTable",
                    "id" => 8,
                    "rename" => "main_table",
                    "action" => "0",
                    "starter_content" => false,
                    "existing_object" => true,
                    "existing_object_name" => "main_table"
                )
            ))
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertCount(2, self::$repository->findAll());
        $decoded_response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $decoded_response["result"]);
        $this->assertCount(1, self::$repository->findBy(array("name" => "main_table_1")));
    }

    public function testSaveActionNew() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/-1/save", array(
            "name" => "new_table",
            "accessibility" => 0
        ));
        $fail_msg = "";
        if (!$client->getResponse()->isSuccessful()) {
            $crawler = $client->getCrawler();
            $fail_msg = $crawler->filter("title")->text();
        }
        $this->assertTrue($client->getResponse()->isSuccessful(), $fail_msg);
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array(
            "result" => 0,
            "errors" => array(),
            "object" => array(
                "class_name" => "DataTable",
                "id" => 2,
                "name" => "new_table",
                "accessibility" => 0,
                "archived" => "0",
                "starterContent" => false,
                "owner" => null,
                "groups" => "",
                "description" => "",
                "updatedOn" => json_decode($client->getResponse()->getContent(), true)["object"]['updatedOn'],
                "columns" => array(),
                "updatedBy" => "admin"
            )), json_decode($client->getResponse()->getContent(), true));
        $this->assertCount(2, self::$repository->findAll());
    }

    public function testSaveActionRename() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/1/save", array(
            "name" => "edited_table",
            "description" => "edited table description",
            "accessibility" => 0
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array(
            "result" => 0,
            "errors" => array(),
            "object" => array(
                "class_name" => "DataTable",
                "id" => 1,
                "name" => "edited_table",
                "accessibility" => 0,
                "archived" => "0",
                "starterContent" => false,
                "owner" => null,
                "groups" => "",
                "description" => "edited table description",
                "updatedOn" => json_decode($client->getResponse()->getContent(), true)["object"]['updatedOn'],
                "columns" => json_decode($client->getResponse()->getContent(), true)["object"]['columns'],
                "updatedBy" => "admin"
            )), json_decode($client->getResponse()->getContent(), true));
        $this->assertCount(1, self::$repository->findAll());
    }

    public function testSaveActionSameName() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/1/save", array(
            "name" => "main_table",
            "description" => "edited table description",
            "accessibility" => 0
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array(
            "result" => 0,
            "errors" => array(),
            "object" => array(
                "class_name" => "DataTable",
                "id" => 1,
                "name" => "main_table",
                "accessibility" => 0,
                "archived" => "0",
                "starterContent" => false,
                "owner" => null,
                "groups" => "",
                "description" => "edited table description",
                "updatedOn" => json_decode($client->getResponse()->getContent(), true)["object"]['updatedOn'],
                "columns" => json_decode($client->getResponse()->getContent(), true)["object"]['columns'],
                "updatedBy" => "admin"
            )), json_decode($client->getResponse()->getContent(), true));
        $this->assertCount(1, self::$repository->findAll());
    }

    public function testSaveActionNameAlreadyExists() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/-1/save", array(
            "name" => "new_table",
            "description" => "table description",
            "accessibility" => 0
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array(
            "result" => 0,
            "errors" => array(),
            "object" => array(
                "class_name" => "DataTable",
                "id" => 2,
                "name" => "new_table",
                "accessibility" => 0,
                "archived" => "0",
                "starterContent" => false,
                "owner" => null,
                "groups" => "",
                "description" => "table description",
                "updatedOn" => json_decode($client->getResponse()->getContent(), true)["object"]['updatedOn'],
                "columns" => json_decode($client->getResponse()->getContent(), true)["object"]['columns'],
                "updatedBy" => "admin"
            )), json_decode($client->getResponse()->getContent(), true));
        $this->assertCount(2, self::$repository->findAll());

        $client->request("POST", "/admin/DataTable/1/save", array(
            "name" => "new_table",
            "description" => "edited table description",
            "accessibility" => 0
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array(
            "result" => 1,
            "object" => null,
            "errors" => array("This name already exists in the system")
                ), json_decode($client->getResponse()->getContent(), true));
        $this->assertCount(2, self::$repository->findAll());
        self::$repository->clear();
        $entity = self::$repository->find(1);
        $this->assertNotNull($entity);
        $this->assertEquals("main_table", $entity->getName());
        $this->assertEquals("table description", $entity->getDescription());
    }

    public function testColumnCollectionAction() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/1/columns/collection");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array(
            array("name" => "id", "type" => "bigint", "nullable" => false),
            array("name" => "temp", "type" => "text", "nullable" => false)
                ), json_decode($client->getResponse()->getContent(), true));
    }

    public function testDataCollectionAction() {
        $client = self::createLoggedClient();
        $expected = array(
            "content" => array(
                array("id" => 1, "temp" => "temp1"),
                array("id" => 2, "temp" => "temp2")
            ),
            "count" => 2
        );

        $client->request("POST", "/admin/DataTable/1/data/collection");

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testDataCollectionActionPrefixed() {
        $client = self::createLoggedClient();
        $expected = array(
            "content" => array(
                array("col_id" => 1, "col_temp" => "temp1"),
                array("col_id" => 2, "col_temp" => "temp2")
            ),
            "count" => 2
        );

        $client->request("POST", "/admin/DataTable/1/data/collection/1");

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    /**
     * Is this test meaningful anymore?
     */
    public function testDataSectionAction() {
        $client = self::createLoggedClient();

        $crawler = $client->request("POST", "/admin/DataTable/1/data/section");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertGreaterThan(0, $crawler->filter("html:contains('Add row')")->count());
    }

    public function testDeleteColumnAction() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/1/column/temp/delete");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array("result" => 0), json_decode($client->getResponse()->getContent(), true));

        $client->request("POST", "/admin/DataTable/1/columns/collection");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array(array("name" => "id", "type" => "bigint", "nullable" => false)), json_decode($client->getResponse()->getContent(), true));
    }

    public function testDeleteRowAction() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/1/row/1/delete");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array("result" => 0), json_decode($client->getResponse()->getContent(), true));

        $expected = array(
            "content" => array(
                array("id" => 2, "temp" => "temp2")
            ),
            "count" => 1
        );
        $client->request("POST", "/admin/DataTable/1/data/collection");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testSaveColumnActionNew() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/1/column/0/save", array(
            "name" => "new_col",
            "type" => "text"
        ));
        $fail_msg = "";
        if (!$client->getResponse()->isSuccessful()) {
            $crawler = $client->getCrawler();
            $fail_msg = $crawler->filter("title")->text();
        }
        $this->assertTrue($client->getResponse()->isSuccessful(), $fail_msg);
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array("result" => 0), json_decode($client->getResponse()->getContent(), true));

        $client->request("POST", "/admin/DataTable/1/columns/collection");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array(
            array("name" => "id", "type" => "bigint", "nullable" => false),
            array("name" => "temp", "type" => "text", "nullable" => false),
            array("name" => "new_col", "type" => "text", "nullable" => false)
                ), json_decode($client->getResponse()->getContent(), true));
    }

    public function testSaveColumnActionSameName() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/1/column/temp/save", array(
            "name" => "temp",
            "type" => "bigint"
        ));
        $fail_msg = "";
        if (!$client->getResponse()->isSuccessful()) {
            $crawler = $client->getCrawler();
            $fail_msg = $crawler->filter("title")->text();
        }
        $this->assertTrue($client->getResponse()->isSuccessful(), $fail_msg);
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        // on PGSQL (and possibly others later) string -> int casts aren't supported
        if (self::$driver_class == 'Doctrine\DBAL\Platforms\PostgreSqlPlatform') {
            $this->assertEquals(
                    array("result" => 2, "errors" => array('Selected type conversion is not supported with configured database driver.')), json_decode($client->getResponse()->getContent(), true)
            );
            return;
        }

        $client->request("POST", "/admin/DataTable/1/columns/collection");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array(
            array("name" => "id", "type" => "bigint", "nullable" => false),
            array("name" => "temp", "type" => "bigint", "nullable" => false)
                ), json_decode($client->getResponse()->getContent(), true));
    }

    public function testSaveColumnActionAlreadyExists() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/1/column/temp/save", array(
            "name" => "id",
            "type" => "text"
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array("result" => 1, "errors" => array("This column already exists in the table")), json_decode($client->getResponse()->getContent(), true));

        $client->request("POST", "/admin/DataTable/1/columns/collection");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array(
            array("name" => "id", "type" => "bigint", "nullable" => false),
            array("name" => "temp", "type" => "text", "nullable" => false)
                ), json_decode($client->getResponse()->getContent(), true));
    }

    public function testSaveColumnAction() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/1/column/temp/save", array(
            "name" => "new_temp",
            "type" => "bigint"
        ));
        $fail_msg = "";
        if (!$client->getResponse()->isSuccessful()) {
            $crawler = $client->getCrawler();
            $fail_msg = $crawler->filter("title")->text();
        }
        $this->assertTrue($client->getResponse()->isSuccessful(), $fail_msg);
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        // on PGSQL (and possibly others later) string -> int casts aren't supported
        if (self::$driver_class == 'Doctrine\DBAL\Platforms\PostgreSqlPlatform') {
            $this->assertEquals(
                    array("result" => 2, "errors" => array('Selected type conversion is not supported with configured database driver.')), json_decode($client->getResponse()->getContent(), true)
            );
            return;
        }

        $this->assertEquals(array("result" => 0), json_decode($client->getResponse()->getContent(), true));

        $client->request("POST", "/admin/DataTable/1/columns/collection");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array(
            array("name" => "id", "type" => "bigint", "nullable" => false),
            array("name" => "new_temp", "type" => "bigint", "nullable" => false)
                ), json_decode($client->getResponse()->getContent(), true));
    }

    public function testInsertRowAction() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/1/row/insert");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array("result" => 0), json_decode($client->getResponse()->getContent(), true));

        $expected = array(
            "content" => array(
                array("id" => 1, "temp" => "temp1"),
                array("id" => 2, "temp" => "temp2"),
                array("id" => 3, "temp" => "")
            ),
            "count" => 3
        );
        $client->request("POST", "/admin/DataTable/1/data/collection");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testUpdateRowAction() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/1/row/2/update", array(
            "values" => array("temp" => "updated_temp2")
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array("result" => 0), json_decode($client->getResponse()->getContent(), true));

        $expected = array(
            "content" => array(
                array("id" => 1, "temp" => "temp1"),
                array("id" => 2, "temp" => "updated_temp2")
            ),
            "count" => 2
        );
        $client->request("POST", "/admin/DataTable/1/data/collection");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testUpdateRowActionPrefixed() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/1/row/2/update/1", array(
            "values" => array("col_temp" => "updated_temp2")
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array("result" => 0), json_decode($client->getResponse()->getContent(), true));

        $expected = array(
            "content" => array(
                array("id" => 1, "temp" => "temp1"),
                array("id" => 2, "temp" => "updated_temp2")
            ),
            "count" => 2
        );
        $client->request("POST", "/admin/DataTable/1/data/collection");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testImportCsvAction() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/DataTable/1/csv/1/1/,/%22/import", array(
            "file" => "csv_table.csv"
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array("result" => 0), json_decode($client->getResponse()->getContent(), true));

        $expected = array(
            "content" => array(
                array("id" => 1, "temp" => "export1"),
                array("id" => 2, "temp" => "export2")
            ),
            "count" => 2
        );
        $client->request("POST", "/admin/DataTable/1/data/collection");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function exportDataProvider() {
        return array(
            array('', true), // default is gzipped 
            array('/compressed', true), // explicitly requesting compression
            array('/plaintext', false)    // requesting plaintext
        );
    }

}
