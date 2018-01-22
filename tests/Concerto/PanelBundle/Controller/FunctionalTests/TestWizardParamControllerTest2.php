<?php

namespace Tests\Concerto\PanelBundle\Controller\FunctionalTests;

use Tests\Concerto\PanelBundle\AFunctionalTest;
use Concerto\PanelBundle\Entity\ATopEntity;

class TestWizardParamControllerTest2 extends AFunctionalTest {

    private static $testRepository;
    private static $testVariableRepository;
    private static $testWizardRepository;
    private static $testWizardStepRepository;
    private static $testWizardParamRepository;
    private static $testNodeRepository;
    private static $testNodePortRepository;
    private static $testNodeConnectionRepository;

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        self::$testRepository = static::$entityManager->getRepository("ConcertoPanelBundle:Test");
        self::$testVariableRepository = static::$entityManager->getRepository("ConcertoPanelBundle:TestVariable");
        self::$testWizardRepository = static::$entityManager->getRepository("ConcertoPanelBundle:TestWizard");
        self::$testWizardStepRepository = static::$entityManager->getRepository("ConcertoPanelBundle:TestWizardStep");
        self::$testWizardParamRepository = static::$entityManager->getRepository("ConcertoPanelBundle:TestWizardParam");
        self::$testNodeRepository = static::$entityManager->getRepository("ConcertoPanelBundle:TestNode");
        self::$testNodePortRepository = static::$entityManager->getRepository("ConcertoPanelBundle:TestNodePort");
        self::$testNodeConnectionRepository = static::$entityManager->getRepository("ConcertoPanelBundle:TestNodeConnection");
    }

    protected function setUp() {
        self::truncateClass("ConcertoPanelBundle:Test");
        self::truncateClass("ConcertoPanelBundle:TestSession");
        self::truncateClass("ConcertoPanelBundle:TestVariable");
        self::truncateClass("ConcertoPanelBundle:TestWizard");
        self::truncateClass("ConcertoPanelBundle:TestWizardStep");
        self::truncateClass("ConcertoPanelBundle:TestWizardParam");
        self::truncateClass("ConcertoPanelBundle:TestNode");
        self::truncateClass("ConcertoPanelBundle:TestNodeConnection");
        self::truncateClass("ConcertoPanelBundle:TestNodePort");

        $client = self::createLoggedClient();

        $client->request("POST", "/admin/Test/import", array(
            "file" => "wizard_params.concerto.json",
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
        $this->assertEquals(11, count(self::$testVariableRepository->findAll()));
        $this->assertEquals(1, count(self::$testWizardRepository->findAll()));
        $this->assertEquals(1, count(self::$testWizardStepRepository->findAll()));
        $this->assertEquals(3, count(self::$testWizardParamRepository->findAll()));
        $this->assertEquals(3, count(self::$testNodeRepository->findAll()));
        $this->assertEquals(6, count(self::$testNodePortRepository->findAll()));
        $this->assertEquals(1, count(self::$testNodeConnectionRepository->findAll()));
    }

    public function testAddNewSimpleParam() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/TestWizardParam/-1/save", array(
            "label" => "wp4",
            "type" => "0",
            "passableThroughUrl" => "0",
            "testVariable" => 5,
            "description" => "",
            "wizard" => 1,
            "wizardStep" => 1,
            "order" => 0,
            "hideCondition" => "",
            "serializedDefinition" => json_encode(array("placeholder" => 0, "defvalue" => "aaa"))
        ));

        //HTTP response
        $fail_msg = "";
        if (!$client->getResponse()->isSuccessful()) {
            $crawler = $client->getCrawler();
            $fail_msg = $crawler->filter("title")->text();
        }
        $this->assertTrue($client->getResponse()->isSuccessful(), $fail_msg);
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(0, json_decode($client->getResponse()->getContent(), true)["result"]);

        //objects count
        $this->assertEquals(3, count(self::$testRepository->findAll()));
        $this->assertEquals(11, count(self::$testVariableRepository->findAll()));
        $this->assertEquals(1, count(self::$testWizardRepository->findAll()));
        $this->assertEquals(1, count(self::$testWizardStepRepository->findAll()));
        $this->assertEquals(4, count(self::$testWizardParamRepository->findAll()));
        $this->assertEquals(3, count(self::$testNodeRepository->findAll()));
        $this->assertEquals(6, count(self::$testNodePortRepository->findAll()));
        $this->assertEquals(1, count(self::$testNodeConnectionRepository->findAll()));

        //check object field values
        $this->assertNotNull(self::$testWizardParamRepository->findOneBy(array("label" => "wp4", "value" => "aaa")), "param wp4 with default value set not found!");
        $test_var = self::$testVariableRepository->findOneBy(array("name" => "p4", "test" => 2, "value" => "aaa"));
        $this->assertNotNull($test_var, "test variable with updated value to default not found!");
        $this->assertNotNull(self::$testNodePortRepository->findOneBy(array("variable" => $test_var, "value" => "aaa")), "test node port with update value set to default not found!");
    }

    public function testAddNewGroupParam() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/TestWizardParam/-1/save", array(
            "label" => "wp4",
            "type" => "9",
            "passableThroughUrl" => "0",
            "testVariable" => 5,
            "description" => "",
            "wizard" => 1,
            "wizardStep" => 1,
            "order" => 0,
            "hideCondition" => "",
            "serializedDefinition" => json_encode(array(
                "fields" => array(
                    array("type" => "0", "name" => "aaa", "label" => "aaa", "definition" => array("placeholder" => 0, "defvalue" => "xxx")),
                    array("type" => "0", "name" => "bbb", "label" => "bbb", "definition" => array("placeholder" => 0, "defvalue" => "yyy"))
                )
        ))));

        //HTTP response
        $fail_msg = "";
        if (!$client->getResponse()->isSuccessful()) {
            $crawler = $client->getCrawler();
            $fail_msg = $crawler->filter("title")->text();
        }
        $this->assertTrue($client->getResponse()->isSuccessful(), $fail_msg);
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(0, json_decode($client->getResponse()->getContent(), true)["result"]);

        //objects count
        $this->assertEquals(3, count(self::$testRepository->findAll()));
        $this->assertEquals(11, count(self::$testVariableRepository->findAll()));
        $this->assertEquals(1, count(self::$testWizardRepository->findAll()));
        $this->assertEquals(1, count(self::$testWizardStepRepository->findAll()));
        $this->assertEquals(4, count(self::$testWizardParamRepository->findAll()));
        $this->assertEquals(3, count(self::$testNodeRepository->findAll()));
        $this->assertEquals(6, count(self::$testNodePortRepository->findAll()));
        $this->assertEquals(1, count(self::$testNodeConnectionRepository->findAll()));

        //check object field values
        $this->assertNotNull(self::$testWizardParamRepository->findOneBy(array("label" => "wp4", "value" => json_encode(array("aaa" => "xxx", "bbb" => "yyy")))), "param wp4 with default value set not found!");
        $test_var = self::$testVariableRepository->findOneBy(array("name" => "p4", "test" => 2, "value" => json_encode(array("aaa" => "xxx", "bbb" => "yyy"))));
        $this->assertNotNull($test_var, "test variable with updated value to default not found!");
        $this->assertNotNull(self::$testNodePortRepository->findOneBy(array("variable" => $test_var, "value" => json_encode(array("aaa" => "xxx", "bbb" => "yyy")))), "test node port with update value set to default not found!");
    }

    public function testAddNewListParam() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/TestWizardParam/-1/save", array(
            "label" => "wp4",
            "type" => "10",
            "passableThroughUrl" => "0",
            "testVariable" => 5,
            "description" => "",
            "wizard" => 1,
            "wizardStep" => 1,
            "order" => 0,
            "hideCondition" => "",
            "serializedDefinition" => json_encode(array(
                "element" => array("type" => "0", "definition" => array("placeholder" => 0, "defvalue" => "aaa"))
        ))));

        //HTTP response
        $fail_msg = "";
        if (!$client->getResponse()->isSuccessful()) {
            $crawler = $client->getCrawler();
            $fail_msg = $crawler->filter("title")->text();
        }
        $this->assertTrue($client->getResponse()->isSuccessful(), $fail_msg);
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(0, json_decode($client->getResponse()->getContent(), true)["result"]);

        //objects count
        $this->assertEquals(3, count(self::$testRepository->findAll()));
        $this->assertEquals(11, count(self::$testVariableRepository->findAll()));
        $this->assertEquals(1, count(self::$testWizardRepository->findAll()));
        $this->assertEquals(1, count(self::$testWizardStepRepository->findAll()));
        $this->assertEquals(4, count(self::$testWizardParamRepository->findAll()));
        $this->assertEquals(3, count(self::$testNodeRepository->findAll()));
        $this->assertEquals(6, count(self::$testNodePortRepository->findAll()));
        $this->assertEquals(1, count(self::$testNodeConnectionRepository->findAll()));

        //check object field values
        $this->assertNotNull(self::$testWizardParamRepository->findOneBy(array("label" => "wp4", "value" => json_encode(array()))), "param wp4 with default value set not found!");
        $test_var = self::$testVariableRepository->findOneBy(array("name" => "p4", "test" => 2, "value" => json_encode(array())));
        $this->assertNotNull($test_var, "test variable with updated value to default not found!");
        $this->assertNotNull(self::$testNodePortRepository->findOneBy(array("variable" => $test_var, "value" => json_encode(array()))), "test node port with update value set to default not found!");

        //add three new elements to list
        $client->request("POST", "/admin/TestWizard/1/save", array(
            "name" => "wizard",
            "description" => "",
            "test" => 1,
            "accessibility" => ATopEntity::ACCESS_PUBLIC,
            "protected" => 0,
            "archived" => 0,
            "groups" => "",
            "serializedSteps" => json_encode(array(
                array(
                    "class_name" => "TestWizardStep",
                    "id" => 1,
                    "title" => "s1",
                    "description" => "",
                    "orderNum" => 0,
                    "colsNum" => 0,
                    "wizard" => 1,
                    "params" => array(
                        array(
                            "class_name" => "TestWizardParam",
                            "id" => 1,
                            "label" => "wp1",
                            "description" => "",
                            "hideCondition" => "",
                            "type" => 0,
                            "passableThroughUrl" => "0",
                            "value" => "zzz",
                            "testVariable" => 2,
                            "name" => "p1",
                            "wizardStep" => 1,
                            "stepTitle" => "s1",
                            "order" => 0,
                            "wizard" => 1,
                            "definition" => array("placeholder" => 0, "defvalue" => "zzz"),
                            "output" => "zzz"
                        ), array(
                            "class_name" => "TestWizardParam",
                            "id" => 2,
                            "label" => "wp2",
                            "description" => "",
                            "hideCondition" => "",
                            "type" => 9,
                            "passableThroughUrl" => "0",
                            "value" => json_encode(array("aaa" => "xxx", "bbb" => "yyy")),
                            "testVariable" => 3,
                            "name" => "p2",
                            "wizardStep" => 1,
                            "stepTitle" => "s1",
                            "order" => 0,
                            "wizard" => 1,
                            "definition" => array(
                                "fields" => array(
                                    array("type" => 0, "name" => "gf1", "label" => "gf1", "definition" => array("placeholder" => 0, "defvalue" => "yyy")),
                                    array("type" => 0, "name" => "gf2", "label" => "gf2", "definition" => array("placeholder" => 0, "defvalue" => "xxx"))
                                )
                            ),
                            "output" => array()
                        ), array(
                            "class_name" => "TestWizardParam",
                            "id" => 3,
                            "label" => "wp3",
                            "description" => "",
                            "hideCondition" => "",
                            "type" => 10,
                            "passableThroughUrl" => "0",
                            "value" => json_encode(array()),
                            "testVariable" => 4,
                            "name" => "p3",
                            "wizardStep" => 1,
                            "stepTitle" => "s1",
                            "order" => 0,
                            "wizard" => 1,
                            "definition" => array(
                                "element" => array(
                                    "type" => 0, "definition" => array("placeholder" => 0, "defvalue" => "lll")
                                )
                            ),
                            "output" => array()
                        ), array(
                            "class_name" => "TestWizardParam",
                            "id" => 4,
                            "label" => "wp4",
                            "description" => "",
                            "hideCondition" => "",
                            "type" => 10,
                            "passableThroughUrl" => "0",
                            "value" => json_encode(array("bbb", "ccc", "ddd")),
                            "testVariable" => 5,
                            "name" => "p4",
                            "wizardStep" => 1,
                            "stepTitle" => "s1",
                            "order" => 0,
                            "wizard" => 1,
                            "definition" => array(
                                "element" => array(
                                    "type" => 0, "definition" => array("placeholder" => 0, "defvalue" => "aaa")
                                )
                            ),
                            "output" => array("bbb", "ccc", "ddd")
                        )
                    )
                )
            ))
        ));
        $fail_msg = "";
        if (!$client->getResponse()->isSuccessful()) {
            $crawler = $client->getCrawler();
            $fail_msg = $crawler->filter("title")->text();
        }
        $this->assertTrue($client->getResponse()->isSuccessful(), $fail_msg);
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(0, json_decode($client->getResponse()->getContent(), true)["result"]);

        //check object field values
        $this->assertNotNull(self::$testWizardParamRepository->findOneBy(array("label" => "wp4", "value" => json_encode(array("bbb", "ccc", "ddd")))), "param wp4 with default value set not found!");
        $test_var = self::$testVariableRepository->findOneBy(array("name" => "p4", "test" => 2, "value" => json_encode(array("bbb", "ccc", "ddd"))));
        $this->assertNotNull($test_var, "test variable with updated value to default not found!");
        $this->assertNotNull(self::$testNodePortRepository->findOneBy(array("variable" => $test_var, "value" => json_encode(array("bbb", "ccc", "ddd")))), "test node port with update value set to default not found!");
    }

    public function testChangeTypeFromSimpleToGroup() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/TestWizardParam/1/save", array(
            "label" => "wp1",
            "type" => "9",
            "passableThroughUrl" => "0",
            "value" => "{}",
            "testVariable" => 2,
            "description" => "",
            "wizard" => 1,
            "wizardStep" => 1,
            "order" => 0,
            "hideCondition" => "",
            "serializedDefinition" => json_encode(array(
                "fields" => array(
                    array("type" => "0", "name" => "mmm", "label" => "mmm", "definition" => array("placeholder" => 0, "defvalue" => "iii")),
                    array("type" => "0", "name" => "nnn", "label" => "nnn", "definition" => array("placeholder" => 0, "defvalue" => "jjj"))
                )
        ))));

        //HTTP response
        $fail_msg = "";
        if (!$client->getResponse()->isSuccessful()) {
            $crawler = $client->getCrawler();
            $fail_msg = $crawler->filter("title")->text();
        }
        $this->assertTrue($client->getResponse()->isSuccessful(), $fail_msg);
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(0, json_decode($client->getResponse()->getContent(), true)["result"]);

        //objects count
        $this->assertEquals(3, count(self::$testRepository->findAll()));
        $this->assertEquals(11, count(self::$testVariableRepository->findAll()));
        $this->assertEquals(1, count(self::$testWizardRepository->findAll()));
        $this->assertEquals(1, count(self::$testWizardStepRepository->findAll()));
        $this->assertEquals(3, count(self::$testWizardParamRepository->findAll()));
        $this->assertEquals(3, count(self::$testNodeRepository->findAll()));
        $this->assertEquals(6, count(self::$testNodePortRepository->findAll()));
        $this->assertEquals(1, count(self::$testNodeConnectionRepository->findAll()));

        //check object field values
        $this->assertNotNull(self::$testWizardParamRepository->findOneBy(array("label" => "wp1", "value" => json_encode(array("mmm" => "iii", "nnn" => "jjj")))), "param wp1 with default value set not found!");
        $test_var = self::$testVariableRepository->findOneBy(array("name" => "p1", "test" => 2, "value" => json_encode(array("mmm" => "iii", "nnn" => "jjj"))));
        $this->assertNotNull($test_var, "test variable with updated value to default not found!");
        $this->assertNotNull(self::$testNodePortRepository->findOneBy(array("variable" => $test_var, "value" => json_encode(array("mmm" => "iii", "nnn" => "jjj")))), "test node port with update value set to default not found!");
    }

    public function testChangeTypeFromGroupToSimple() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/TestWizardParam/2/save", array(
            "label" => "wp2",
            "type" => "0",
            "passableThroughUrl" => "0",
            "value" => "",
            "testVariable" => 3,
            "description" => "",
            "wizard" => 1,
            "wizardStep" => 1,
            "order" => 0,
            "hideCondition" => "",
            "serializedDefinition" => json_encode(array("placeholder" => 0, "defvalue" => "qqq"))));

        //HTTP response
        $fail_msg = "";
        if (!$client->getResponse()->isSuccessful()) {
            $crawler = $client->getCrawler();
            $fail_msg = $crawler->filter("title")->text();
        }
        $this->assertTrue($client->getResponse()->isSuccessful(), $fail_msg);
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(0, json_decode($client->getResponse()->getContent(), true)["result"]);

        //objects count
        $this->assertEquals(3, count(self::$testRepository->findAll()));
        $this->assertEquals(11, count(self::$testVariableRepository->findAll()));
        $this->assertEquals(1, count(self::$testWizardRepository->findAll()));
        $this->assertEquals(1, count(self::$testWizardStepRepository->findAll()));
        $this->assertEquals(3, count(self::$testWizardParamRepository->findAll()));
        $this->assertEquals(3, count(self::$testNodeRepository->findAll()));
        $this->assertEquals(6, count(self::$testNodePortRepository->findAll()));
        $this->assertEquals(1, count(self::$testNodeConnectionRepository->findAll()));

        //check object field values
        $this->assertNotNull(self::$testWizardParamRepository->findOneBy(array("label" => "wp2", "value" => "qqq")), "param wp2 with default value set not found!");
        $test_var = self::$testVariableRepository->findOneBy(array("name" => "p2", "test" => 2, "value" => "qqq"));
        $this->assertNotNull($test_var, "test variable with updated value to default not found!");
        $this->assertNotNull(self::$testNodePortRepository->findOneBy(array("variable" => $test_var, "value" => "qqq")), "test node port with update value set to default not found!");
    }

}
