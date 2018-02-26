<?php

namespace Tests\Concerto\PanelBundle\Controller\FunctionalTests;

use Tests\Concerto\PanelBundle\AFunctionalTest;
use Concerto\PanelBundle\Entity\ATopEntity;
use Concerto\PanelBundle\Entity\Test;

class TestWizardControllerTest extends AFunctionalTest {

    private static $repository;
    private static $wizardParamsRepository;
    private static $wizardStepsRepository;

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        self::$repository = static::$entityManager->getRepository("ConcertoPanelBundle:TestWizard");
        self::$wizardParamsRepository = static::$entityManager->getRepository("ConcertoPanelBundle:TestWizardParam");
        self::$wizardStepsRepository = static::$entityManager->getRepository("ConcertoPanelBundle:TestWizardStep");
    }

    protected function setUp() {
        parent::setUp();

        $client = self::createLoggedClient();
        $client->request("POST", "/admin/Test/-1/save", array(
            "name" => "test2",
            "description" => "description",
            "code" => "print('start')",
            "visibility" => Test::VISIBILITY_REGULAR,
            "type" => Test::TYPE_CODE,
            "accessibility" => ATopEntity::ACCESS_PUBLIC
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $content["result"]);

        $client->request("POST", "/admin/TestWizard/-1/save", array(
            "name" => "wizard",
            "description" => "description",
            "accessibility" => ATopEntity::ACCESS_PUBLIC,
            "test" => 1
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $content["result"]);

        $client->request("POST", "/admin/TestVariable/-1/save", array(
            "name" => "login",
            "test" => 1,
            "type" => 0,
            "passableThroughUrl" => "1"
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $content["result"]);

        $client->request("POST", "/admin/TestWizardStep/-1/save", array(
            "title" => "step1",
            "description" => "First step",
            "orderNum" => "0",
            "wizard" => 1
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $content["result"]);

        $client->request("POST", "/admin/TestWizardParam/-1/save", array(
            "label" => "param1",
            "type" => 2,
            "passableThroughUrl" => "1",
            "testVariable" => 2,
            "description" => "wiz param desc",
            "hideCondition" => "",
            "wizard" => 1,
            "wizardStep" => 1,
            "order" => 0,
            "serializedDefinition" => json_encode(array("placeholder" => 0))
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $content["result"]);
    }

    public function testCollectionAction() {
        $client = self::createLoggedClient();

        $client->request('POST', '/admin/TestWizard/collection');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $expected = array(
            array(
                "class_name" => "TestWizard",
                "id" => 1,
                "name" => "wizard",
                "description" => "description",
                "archived" => "0",
                "starterContent" => false,
                "owner" => null,
                "groups" => "",
                "steps" => array(
                    array(
                        "class_name" => "TestWizardStep",
                        "id" => 1,
                        "title" => "step1",
                        "description" => "First step",
                        "orderNum" => 0,
                        "wizard" => 1,
                        "colsNum" => 0,
                        "params" => array(
                            array(
                                "class_name" => "TestWizardParam",
                                "id" => 1,
                                "label" => "param1",
                                "type" => 2,
                                "passableThroughUrl" => "1",
                                "testVariable" => 2,
                                "name" => "login",
                                "description" => "wiz param desc",
                                "wizard" => 1,
                                "wizardStep" => 1,
                                "stepTitle" => "step1",
                                "order" => 0,
                                "value" => null,
                                "hideCondition" => "",
                                "definition" => array(
                                    "placeholder" => 0
                                )
                            )
                        )
                    )
                ),
                "test" => 1,
                "testName" => "test2",
                "updatedOn" => json_decode($client->getResponse()->getContent(), true)[0]['updatedOn'],
                "updatedBy" => "admin",
                "accessibility" => ATopEntity::ACCESS_PUBLIC
            )
        );
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testFormActionNew() {
        $client = self::createLoggedClient();

        $crawler = $client->request("POST", "/admin/TestWizard/form/add");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertGreaterThan(0, $crawler->filter("input[type='text'][ng-model='object.name']")->count());
    }

    public function testFormActionEdit() {
        $client = self::createLoggedClient();

        $crawler = $client->request("POST", "/admin/TestWizard/form/edit");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertGreaterThan(0, $crawler->filter("input[type='text'][ng-model='object.name']")->count());
    }

    public function testDeleteAction() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/TestWizard/1/delete");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array("result" => 0, "object_ids" => 1), json_decode($client->getResponse()->getContent(), true));
        $this->assertCount(0, self::$repository->findAll());
        $this->assertCount(0, self::$wizardStepsRepository->findAll());
        $this->assertCount(0, self::$wizardParamsRepository->findAll());
    }

    public function testSaveActionNew() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/TestWizard/-1/save", array(
            "name" => "new_wizard",
            "description" => "desc",
            "test" => 1,
            "accessibility" => ATopEntity::ACCESS_PUBLIC
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array(
            "result" => 0,
            "errors" => array(),
            "object" => array(
                "class_name" => "TestWizard",
                "id" => 2,
                "name" => "new_wizard",
                "description" => "desc",
                "steps" => array(),
                "test" => 1,
                "testName" => "test2",
                "archived" => "0",
                "starterContent" => false,
                "owner" => null,
                "groups" => "",
                "updatedOn" => json_decode($client->getResponse()->getContent(), true)["object"]['updatedOn'],
                "updatedBy" => "admin",
                "accessibility" => ATopEntity::ACCESS_PUBLIC
            )), json_decode($client->getResponse()->getContent(), true));
        $this->assertCount(2, self::$repository->findAll());
    }

    public function testSaveActionRename() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/TestWizard/1/save", array(
            "name" => "edited_wizard",
            "description" => "edited wizard description",
            "test" => "1",
            "accessibility" => ATopEntity::ACCESS_PUBLIC
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array(
            "result" => 0,
            "errors" => array(),
            "object" => array(
                "class_name" => "TestWizard",
                "id" => 1,
                "name" => "edited_wizard",
                "description" => "edited wizard description",
                "archived" => "0",
                "starterContent" => false,
                "owner" => null,
                "groups" => "",
                "steps" => array(
                    array(
                        "class_name" => "TestWizardStep",
                        "id" => 1,
                        "title" => "step1",
                        "description" => "First step",
                        "orderNum" => 0,
                        "wizard" => 1,
                        "colsNum" => 0,
                        "params" => array(
                            array(
                                "class_name" => "TestWizardParam",
                                "id" => 1,
                                "label" => "param1",
                                "type" => 2,
                                "passableThroughUrl" => "1",
                                "testVariable" => 2,
                                "name" => "login",
                                "description" => "wiz param desc",
                                "wizard" => 1,
                                "wizardStep" => 1,
                                "stepTitle" => "step1",
                                "order" => 0,
                                "value" => null,
                                "hideCondition" => "",
                                "definition" => array("placeholder" => 0)
                            )
                        )
                    )
                ),
                "test" => 1,
                "testName" => "test2",
                "updatedOn" => json_decode($client->getResponse()->getContent(), true)["object"]['updatedOn'],
                "updatedBy" => "admin",
                "accessibility" => ATopEntity::ACCESS_PUBLIC
            )), json_decode($client->getResponse()->getContent(), true));
        $this->assertCount(1, self::$repository->findAll());
    }

    public function testSaveActionSameName() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/TestWizard/1/save", array(
            "name" => "wizard",
            "description" => "edited wizard description",
            "test" => "1",
            "accessibility" => ATopEntity::ACCESS_PUBLIC
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array(
            "result" => 0,
            "errors" => array(),
            "object" => array(
                "class_name" => "TestWizard",
                "id" => 1,
                "name" => "wizard",
                "description" => "edited wizard description",
                "archived" => "0",
                "starterContent" => false,
                "owner" => null,
                "groups" => "",
                "steps" => array(
                    array(
                        "class_name" => "TestWizardStep",
                        "id" => 1,
                        "title" => "step1",
                        "description" => "First step",
                        "orderNum" => 0,
                        "wizard" => 1,
                        "colsNum" => 0,
                        "params" => array(
                            array(
                                "class_name" => "TestWizardParam",
                                "id" => 1,
                                "label" => "param1",
                                "type" => 2,
                                "passableThroughUrl" => "1",
                                "testVariable" => 2,
                                "name" => "login",
                                "description" => "wiz param desc",
                                "wizard" => 1,
                                "wizardStep" => 1,
                                "stepTitle" => "step1",
                                "order" => 0,
                                "value" => null,
                                "hideCondition" => "",
                                "definition" => array("placeholder" => 0)
                            )
                        )
                    )
                ),
                "test" => 1,
                "testName" => "test2",
                "updatedOn" => json_decode($client->getResponse()->getContent(), true)["object"]['updatedOn'],
                "updatedBy" => "admin",
                "accessibility" => ATopEntity::ACCESS_PUBLIC
            )), json_decode($client->getResponse()->getContent(), true));
        $this->assertCount(1, self::$repository->findAll());
    }

    public function testSaveActionNameAlreadyExists() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/TestWizard/-1/save", array(
            "name" => "new_wizard",
            "description" => "desc",
            "test" => 1,
            "accessibility" => ATopEntity::ACCESS_PUBLIC
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array(
            "result" => 0,
            "errors" => array(),
            "object" => array(
                "class_name" => "TestWizard",
                "id" => 2,
                "name" => "new_wizard",
                "description" => "desc",
                "steps" => array(),
                "test" => 1,
                "testName" => "test2",
                "updatedOn" => json_decode($client->getResponse()->getContent(), true)["object"]['updatedOn'],
                "updatedBy" => "admin",
                "accessibility" => ATopEntity::ACCESS_PUBLIC,
                "archived" => "0",
                "starterContent" => false,
                "owner" => null,
                "groups" => ""
            )), json_decode($client->getResponse()->getContent(), true));
        $this->assertCount(2, self::$repository->findAll());

        $client->request("POST", "/admin/TestWizard/2/save", array(
            "name" => "wizard",
            "description" => "edited view description",
            "test" => 1,
            "accessibility" => ATopEntity::ACCESS_PUBLIC
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array(
            "result" => 1,
            "object" => null,
            "errors" => array("This name already exists in the system")
                ), json_decode($client->getResponse()->getContent(), true));
        $this->assertCount(2, self::$repository->findAll());
    }

}
