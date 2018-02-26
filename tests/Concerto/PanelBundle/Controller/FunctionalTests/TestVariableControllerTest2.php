<?php

namespace Tests\Concerto\PanelBundle\Controller\FunctionalTests;

use Tests\Concerto\PanelBundle\AFunctionalTest;

class TestVariableControllerTest2 extends AFunctionalTest {

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

    public function testAddNewVariable() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/TestVariable/-1/save", array(
            "name" => "aaa",
            "type" => "0",
            "passableThroughUrl" => "0",
            "test" => 1,
            "description" => ""
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
        $this->assertEquals(13, count(self::$testVariableRepository->findAll()));
        $this->assertEquals(1, count(self::$testWizardRepository->findAll()));
        $this->assertEquals(1, count(self::$testWizardStepRepository->findAll()));
        $this->assertEquals(3, count(self::$testWizardParamRepository->findAll()));
        $this->assertEquals(3, count(self::$testNodeRepository->findAll()));
        $this->assertEquals(7, count(self::$testNodePortRepository->findAll()), "additional node port object not created after adding test variable!");
        $this->assertEquals(1, count(self::$testNodeConnectionRepository->findAll()));

        //check object field values
        $parent_var = self::$testVariableRepository->findOneBy(array("name" => "aaa", "test" => 1, "type" => "0"));
        $this->assertNotNull($parent_var, "added variable not found!");
        $child_var = self::$testVariableRepository->findOneBy(array("name" => "aaa", "test" => 2, "type" => "0", "parentVariable" => $parent_var));
        $this->assertNotNull($child_var, "child variable not found!");
        $this->assertNotNull(self::$testNodePortRepository->findOneBy(array("variable" => $child_var, "value" => null)), "test node port of newly added test variable not found!");
    }

}
