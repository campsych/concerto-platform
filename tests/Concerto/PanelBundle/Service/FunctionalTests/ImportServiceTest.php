<?php

namespace Tests\Concerto\PanelBundle\Service\FunctionalTests;

use Tests\Concerto\PanelBundle\AFunctionalTest;

class ImportServiceTest extends AFunctionalTest
{

    private static $testRepository;
    private static $testVariableRepository;
    private static $testWizardRepository;
    private static $testWizardStepRepository;
    private static $testWizardParamRepository;
    private static $testNodeRepository;
    private static $testNodePortRepository;
    private static $testNodeConnectionRepository;
    private static $viewTemplateRepository;
    private static $dataTableRepository;

    private function dropTable($name)
    {
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

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::$testRepository = static::$entityManager->getRepository("ConcertoPanelBundle:Test");
        self::$testVariableRepository = static::$entityManager->getRepository("ConcertoPanelBundle:TestVariable");
        self::$testWizardRepository = static::$entityManager->getRepository("ConcertoPanelBundle:TestWizard");
        self::$testWizardStepRepository = static::$entityManager->getRepository("ConcertoPanelBundle:TestWizardStep");
        self::$testWizardParamRepository = static::$entityManager->getRepository("ConcertoPanelBundle:TestWizardParam");
        self::$testNodeRepository = static::$entityManager->getRepository("ConcertoPanelBundle:TestNode");
        self::$testNodePortRepository = static::$entityManager->getRepository("ConcertoPanelBundle:TestNodePort");
        self::$testNodeConnectionRepository = static::$entityManager->getRepository("ConcertoPanelBundle:TestNodeConnection");
        self::$testNodeConnectionRepository = static::$entityManager->getRepository("ConcertoPanelBundle:TestNodeConnection");
        self::$viewTemplateRepository = static::$entityManager->getRepository("ConcertoPanelBundle:ViewTemplate");
        self::$dataTableRepository = static::$entityManager->getRepository("ConcertoPanelBundle:DataTable");
    }

    protected function setUp()
    {
        self::truncateClass("ConcertoPanelBundle:Test");
        self::truncateClass("ConcertoPanelBundle:TestSession");
        self::truncateClass("ConcertoPanelBundle:TestVariable");
        self::truncateClass("ConcertoPanelBundle:TestWizard");
        self::truncateClass("ConcertoPanelBundle:TestWizardStep");
        self::truncateClass("ConcertoPanelBundle:TestWizardParam");
        self::truncateClass("ConcertoPanelBundle:TestNode");
        self::truncateClass("ConcertoPanelBundle:TestNodeConnection");
        self::truncateClass("ConcertoPanelBundle:TestNodePort");
        self::truncateClass("ConcertoPanelBundle:ViewTemplate");
        self::truncateClass("ConcertoPanelBundle:DataTable");
        $this->dropTable("data");
    }

    public function testFlowConvertRenamedSourceVariable()
    {
        $client = self::createLoggedClient();

        /* IMPORT NEW TEST */

        $client->request("POST", "/admin/Test/import", array(
            "file" => "nested_flow1.concerto.json",
            "instructions" => json_encode(array(
                array(
                    "class_name" => "Test",
                    "id" => 1,
                    "rename" => "source",
                    "action" => "0",
                    "starter_content" => false,
                    "existing_object" => false,
                    "existing_object_name" => null,
                    "can_ignore" => false
                ),
                array(
                    "class_name" => "TestWizard",
                    "id" => 1,
                    "rename" => "wizard",
                    "action" => "0",
                    "starter_content" => false,
                    "existing_object" => false,
                    "existing_object_name" => null,
                    "can_ignore" => false
                ),
                array(
                    "class_name" => "Test",
                    "id" => 2,
                    "rename" => "test",
                    "action" => "0",
                    "starter_content" => false,
                    "existing_object" => false,
                    "existing_object_name" => null,
                    "can_ignore" => false
                ),
                array(
                    "class_name" => "Test",
                    "id" => 3,
                    "rename" => "flow",
                    "action" => "0",
                    "starter_content" => false,
                    "existing_object" => false,
                    "existing_object_name" => null,
                    "can_ignore" => false
                )
            ))
        ));
        //HTTP response
        $fail_msg = "";
        if (!$client->getResponse()->isSuccessful()) {
            $crawler = $client->getCrawler();
            $fail_msg = $crawler->filter("title")->text();
        }
        $this->assertTrue($client->getResponse()->isSuccessful(), $fail_msg);
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        //objects count
        $this->assertEquals(3, count(self::$testRepository->findAll()));
        $this->assertEquals(9, count(self::$testVariableRepository->findAll()));
        $this->assertEquals(1, count(self::$testWizardRepository->findAll()));
        $this->assertEquals(1, count(self::$testWizardStepRepository->findAll()));
        $this->assertEquals(2, count(self::$testWizardParamRepository->findAll()));
        $this->assertEquals(3, count(self::$testNodeRepository->findAll()));
        $this->assertEquals(5, count(self::$testNodePortRepository->findAll()));
        $this->assertEquals(1, count(self::$testNodeConnectionRepository->findAll()));

        /* IMPORT CONVERT TEST */

        $client->request("POST", "/admin/Test/import", array(
            "file" => "nested_flow2.concerto.json",
            "instructions" => json_encode(array(
                array(
                    "class_name" => "Test",
                    "id" => 1,
                    "rename" => "source",
                    "action" => "1",
                    "starter_content" => false,
                    "existing_object" => true,
                    "existing_object_name" => "source",
                    "can_ignore" => false
                ),
                array(
                    "class_name" => "TestWizard",
                    "id" => 1,
                    "rename" => "wizard",
                    "action" => "1",
                    "starter_content" => false,
                    "existing_object" => true,
                    "existing_object_name" => "wizard",
                    "can_ignore" => false
                ),
                array(
                    "class_name" => "Test",
                    "id" => 2,
                    "rename" => "test",
                    "action" => "1",
                    "starter_content" => false,
                    "existing_object" => true,
                    "existing_object_name" => "test",
                    "can_ignore" => false
                ),
                array(
                    "class_name" => "Test",
                    "id" => 3,
                    "rename" => "flow",
                    "action" => "1",
                    "starter_content" => false,
                    "existing_object" => true,
                    "existing_object_name" => "flow",
                    "can_ignore" => false
                )
            ))
        ));
        //HTTP response
        $fail_msg = "";
        if (!$client->getResponse()->isSuccessful()) {
            $crawler = $client->getCrawler();
            $fail_msg = $crawler->filter("title")->text();
        }
        $this->assertTrue($client->getResponse()->isSuccessful(), $fail_msg);
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        //objects count
        $this->assertEquals(3, count(self::$testRepository->findAll()));
        $this->assertEquals(11, count(self::$testVariableRepository->findAll()));
        $this->assertEquals(1, count(self::$testWizardRepository->findAll()));
        $this->assertEquals(1, count(self::$testWizardStepRepository->findAll()));
        $this->assertEquals(3, count(self::$testWizardParamRepository->findAll()));
        $this->assertEquals(3, count(self::$testNodeRepository->findAll()));
        $this->assertEquals(5, count(self::$testNodePortRepository->findAll()));
        $this->assertEquals(1, count(self::$testNodeConnectionRepository->findAll()));

        //changed objects
        $this->assertNotNull(self::$testVariableRepository->findOneBy(array("name" => "np1")), "renamed np1 TestVariable not found!");
        $this->assertNotNull(self::$testNodePortRepository->findOneBy(array("value" => "nflow_wp1", "defaultValue" => "0")), "new value in primitive level port not found!");
        $this->assertNotNull(self::$testNodePortRepository->findOneBy(array("value" => "{\"wp2f1\":\"nflow_wp2f1\",\"wp2f2\":\"nflow_wp2f2\"}", "defaultValue" => "0")), "new value in group port not found!");
    }

    public function testFlowConvertKeepPortValues()
    {
        $client = self::createLoggedClient();

        /* IMPORT NEW TEST */

        $client->request("POST", "/admin/Test/import", array(
            "file" => "port_default_full.concerto.json",
            "instructions" => '[{"id":1,"name":"source","class_name":"Test","action":"0","rename":"source","starter_content":false,"existing_object":false,"existing_object_name":null,"can_ignore":false},{"id":1,"name":"wizard","class_name":"TestWizard","action":"0","rename":"wizard","starter_content":false,"existing_object":false,"existing_object_name":null,"can_ignore":false},{"id":2,"name":"test","class_name":"Test","action":"0","rename":"test","starter_content":false,"existing_object":false,"existing_object_name":null,"can_ignore":false},{"id":3,"name":"demo","class_name":"Test","action":"0","rename":"demo","starter_content":false,"existing_object":false,"existing_object_name":null,"can_ignore":false}]'
        ));
        //HTTP response
        $fail_msg = "";
        if (!$client->getResponse()->isSuccessful()) {
            $crawler = $client->getCrawler();
            $fail_msg = $crawler->filter("title")->text();
        }
        $this->assertTrue($client->getResponse()->isSuccessful(), $fail_msg);
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        //objects count
        $this->assertEquals(3, count(self::$testRepository->findAll()));
        $this->assertEquals(7, count(self::$testVariableRepository->findAll()));
        $this->assertEquals(1, count(self::$testWizardRepository->findAll()));
        $this->assertEquals(1, count(self::$testWizardStepRepository->findAll()));
        $this->assertEquals(2, count(self::$testWizardParamRepository->findAll()));
        $this->assertEquals(3, count(self::$testNodeRepository->findAll()));
        $this->assertEquals(4, count(self::$testNodePortRepository->findAll()));

        /* IMPORT CONVERT TEST */

        $client->request("POST", "/admin/Test/import", array(
            "file" => "port_default_source.concerto.json",
            "instructions" => '[{"id":1,"name":"source","class_name":"Test","action":"1","rename":"source","starter_content":false,"existing_object":true,"existing_object_name":"source","can_ignore":true},{"id":1,"name":"wizard","class_name":"TestWizard","action":"1","rename":"wizard","starter_content":false,"existing_object":true,"existing_object_name":"wizard","can_ignore":true},{"id":2,"name":"test","class_name":"Test","action":"1","rename":"test","starter_content":false,"existing_object":true,"existing_object_name":"test","can_ignore":true}]'
        ));
        //HTTP response
        $fail_msg = "";
        if (!$client->getResponse()->isSuccessful()) {
            $crawler = $client->getCrawler();
            $fail_msg = $crawler->filter("title")->text();
        }
        $this->assertTrue($client->getResponse()->isSuccessful(), $fail_msg);
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        //objects count
        $this->assertEquals(3, count(self::$testRepository->findAll()));
        $this->assertEquals(7, count(self::$testVariableRepository->findAll()));
        $this->assertEquals(1, count(self::$testWizardRepository->findAll()));
        $this->assertEquals(1, count(self::$testWizardStepRepository->findAll()));
        $this->assertEquals(2, count(self::$testWizardParamRepository->findAll()));
        $this->assertEquals(3, count(self::$testNodeRepository->findAll()));
        $this->assertEquals(4, count(self::$testNodePortRepository->findAll()));

        //changed objects
        $this->assertNotNull(self::$testNodePortRepository->findOneBy(array("value" => "bbb", "defaultValue" => "0")), "simple type port value lost!");
        $this->assertNotNull(self::$testNodePortRepository->findOneBy(array("value" => "[\"ccc\",\"ddd\",\"eee\"]", "defaultValue" => "0")), "complex type port value lost!");
    }

    public function testFlowConvertDuplicatePort()
    {
        $client = self::createLoggedClient();

        /* IMPORT NEW TEST */

        $client->request("POST", "/admin/Test/import", array(
            "file" => "nested_port_duplicate1.concerto.json",
            "instructions" => json_encode(array(
                array(
                    "class_name" => "Test",
                    "id" => 1,
                    "rename" => "source_info",
                    "action" => "0",
                    "starter_content" => false,
                    "existing_object" => false,
                    "existing_object_name" => null,
                    "can_ignore" => false
                ),
                array(
                    "class_name" => "TestWizard",
                    "id" => 1,
                    "rename" => "info",
                    "action" => "0",
                    "starter_content" => true,
                    "existing_object" => false,
                    "existing_object_name" => null,
                    "can_ignore" => false
                ),
                array(
                    "class_name" => "Test",
                    "id" => 2,
                    "rename" => "info",
                    "action" => "0",
                    "starter_content" => true,
                    "existing_object" => false,
                    "existing_object_name" => null,
                    "can_ignore" => false
                ),
                array(
                    "class_name" => "Test",
                    "id" => 3,
                    "rename" => "flow",
                    "action" => "0",
                    "starter_content" => false,
                    "existing_object" => false,
                    "existing_object_name" => null,
                    "can_ignore" => false
                )
            ))
        ));
        //HTTP response
        $fail_msg = "";
        if (!$client->getResponse()->isSuccessful()) {
            $crawler = $client->getCrawler();
            $fail_msg = $crawler->filter("title")->text();
        }
        $this->assertTrue($client->getResponse()->isSuccessful(), $fail_msg);
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        //objects count
        $this->assertEquals(3, count(self::$testRepository->findAll()));
        $this->assertEquals(19, count(self::$testVariableRepository->findAll()));
        $this->assertEquals(1, count(self::$testWizardRepository->findAll()));
        $this->assertEquals(1, count(self::$testWizardStepRepository->findAll()));
        $this->assertEquals(5, count(self::$testWizardParamRepository->findAll()));
        $this->assertEquals(3, count(self::$testNodeRepository->findAll()));
        $this->assertEquals(10, count(self::$testNodePortRepository->findAll()));
        $this->assertEquals(1, count(self::$testNodeConnectionRepository->findAll()));

        /* IMPORT CONVERT TEST */

        $client->request("POST", "/admin/Test/import", array(
            "file" => "nested_port_duplicate2.concerto.json",
            "instructions" => json_encode(array(
                array(
                    "class_name" => "Test",
                    "id" => 1,
                    "rename" => "source_info",
                    "action" => "1",
                    "starter_content" => false,
                    "existing_object" => true,
                    "existing_object_name" => "source_info",
                    "can_ignore" => false
                ),
                array(
                    "class_name" => "TestWizard",
                    "id" => 1,
                    "rename" => "info",
                    "action" => "1",
                    "starter_content" => true,
                    "existing_object" => true,
                    "existing_object_name" => "source_info",
                    "can_ignore" => false
                ),
                array(
                    "class_name" => "Test",
                    "id" => 2,
                    "rename" => "info",
                    "action" => "1",
                    "starter_content" => true,
                    "existing_object" => true,
                    "existing_object_name" => "source_info",
                    "can_ignore" => false
                )
            ))
        ));
        //HTTP response
        $fail_msg = "";
        if (!$client->getResponse()->isSuccessful()) {
            $crawler = $client->getCrawler();
            $fail_msg = $crawler->filter("title")->text();
        }
        $this->assertTrue($client->getResponse()->isSuccessful(), $fail_msg);
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        //objects count
        $this->assertEquals(3, count(self::$testRepository->findAll()));
        $this->assertEquals(21, count(self::$testVariableRepository->findAll()));
        $this->assertEquals(1, count(self::$testWizardRepository->findAll()));
        $this->assertEquals(1, count(self::$testWizardStepRepository->findAll()));
        $this->assertEquals(5, count(self::$testWizardParamRepository->findAll()));
        $this->assertEquals(3, count(self::$testNodeRepository->findAll()));
        $this->assertEquals(11, count(self::$testNodePortRepository->findAll()));
        $this->assertEquals(1, count(self::$testNodeConnectionRepository->findAll()));

        //changed objects
        $new_vars = self::$testVariableRepository->findBy(array("name" => "new_var"));
        $this->assertCount(2, $new_vars, "renamed np1 TestVariable not found!");
        $new_var_ports_count = 0;
        foreach ($new_vars as $var) {
            $new_var_ports_count += $var->getPorts()->count();
        }
        $this->assertEquals(1, $new_var_ports_count, "More than one new_var port!");
    }

    public function testViewTemplateConvert()
    {
        $client = self::createLoggedClient();

        /* IMPORT NEW VIEW TEMPLATE */

        $client->request("POST", "/admin/ViewTemplate/import", array(
            "file" => "view1.concerto.json",
            "instructions" => json_encode(array(
                array(
                    "class_name" => "ViewTemplate",
                    "id" => 1,
                    "rename" => "view",
                    "action" => "0",
                    "starter_content" => false,
                    "existing_object" => false,
                    "existing_object_name" => null,
                    "can_ignore" => false
                )
            ))
        ));
        //HTTP response
        $fail_msg = "";
        if (!$client->getResponse()->isSuccessful()) {
            $crawler = $client->getCrawler();
            $fail_msg = $crawler->filter("title")->text();
        }
        $this->assertTrue($client->getResponse()->isSuccessful(), $fail_msg);
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        //objects count
        $this->assertEquals(1, count(self::$viewTemplateRepository->findAll()));

        //object properties
        $this->assertEquals(1, count(self::$viewTemplateRepository->findBy(array("name" => "view", "head" => "aaa", "css" => "bbb", "js" => "ccc", "html" => "ddd"))));

        /* IMPORT CONVERT VIEW TEMPLATE */

        $client->request("POST", "/admin/ViewTemplate/import", array(
            "file" => "view2.concerto.json",
            "instructions" => json_encode(array(
                array(
                    "class_name" => "ViewTemplate",
                    "id" => 1,
                    "rename" => "view",
                    "action" => "1",
                    "starter_content" => false,
                    "existing_object" => true,
                    "existing_object_name" => "view",
                    "can_ignore" => false
                )
            ))
        ));
        //HTTP response
        $fail_msg = "";
        if (!$client->getResponse()->isSuccessful()) {
            $crawler = $client->getCrawler();
            $fail_msg = $crawler->filter("title")->text();
        }
        $this->assertTrue($client->getResponse()->isSuccessful(), $fail_msg);
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        //objects count
        $this->assertEquals(1, count(self::$viewTemplateRepository->findAll()));

        //changed objects
        $this->assertEquals(1, count(self::$viewTemplateRepository->findBy(array("name" => "view", "head" => "xxx", "css" => "yyy", "js" => "zzz", "html" => "qqq"))));
    }

    public function testDataTableConvert()
    {
        $client = self::createLoggedClient();

        /* IMPORT NEW DATA TABLE */

        $client->request("POST", "/admin/DataTable/import", array(
            "file" => "data1.concerto.json",
            "instructions" => json_encode(array(
                array(
                    "class_name" => "DataTable",
                    "id" => 1,
                    "rename" => "data",
                    "action" => "0",
                    "starter_content" => false,
                    "existing_object" => false,
                    "existing_object_name" => null,
                    "can_ignore" => false
                )
            ))
        ));
        //HTTP response
        $fail_msg = "";
        if (!$client->getResponse()->isSuccessful()) {
            $crawler = $client->getCrawler();
            $fail_msg = $crawler->filter("title")->text();
        }
        $this->assertTrue($client->getResponse()->isSuccessful(), $fail_msg);
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        //objects count
        $this->assertEquals(1, count(self::$dataTableRepository->findAll()));

        //check data
        $client->request("GET", "/admin/DataTable/1/data/collection");
        //HTTP response
        $fail_msg = "";
        if (!$client->getResponse()->isSuccessful()) {
            $crawler = $client->getCrawler();
            $fail_msg = $crawler->filter("title")->text();
        }
        $this->assertTrue($client->getResponse()->isSuccessful(), $fail_msg);
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array(
            "content" => array(),
            "count" => 0), json_decode($client->getResponse()->getContent(), true));

        /* IMPORT CONVERT DATA TABLE */

        $client->request("POST", "/admin/DataTable/import", array(
            "file" => "data2.concerto.json",
            "instructions" => json_encode(array(
                array(
                    "class_name" => "DataTable",
                    "id" => 1,
                    "rename" => "data",
                    "action" => "1",
                    "starter_content" => false,
                    "existing_object" => true,
                    "existing_object_name" => "data",
                    "can_ignore" => false
                )
            ))
        ));
        //HTTP response
        $fail_msg = "";
        if (!$client->getResponse()->isSuccessful()) {
            $crawler = $client->getCrawler();
            $fail_msg = $crawler->filter("title")->text();
        }
        $this->assertTrue($client->getResponse()->isSuccessful(), $fail_msg);
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        //objects count
        $this->assertEquals(1, count(self::$dataTableRepository->findAll()));

        //check data
        $client->request("GET", "/admin/DataTable/1/data/collection");
        //HTTP response
        $fail_msg = "";
        if (!$client->getResponse()->isSuccessful()) {
            $crawler = $client->getCrawler();
            $fail_msg = $crawler->filter("title")->text();
        }
        $this->assertTrue($client->getResponse()->isSuccessful(), $fail_msg);
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        //same data, data not converted
        $this->assertEquals(array(
            "content" => array(),
            "count" => 0), json_decode($client->getResponse()->getContent(), true));
    }

}
