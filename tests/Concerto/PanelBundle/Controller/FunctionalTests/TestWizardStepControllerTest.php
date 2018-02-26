<?php

namespace Tests\Concerto\PanelBundle\Controller\FunctionalTests;

use Tests\Concerto\PanelBundle\AFunctionalTest;
use Concerto\PanelBundle\Entity\ATopEntity;
use Concerto\PanelBundle\Entity\Test;

class TestWizardStepControllerTest extends AFunctionalTest {

    private static $repository;
    private static $paramsRepository;

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        self::$repository = static::$entityManager->getRepository("ConcertoPanelBundle:TestWizardStep");
        self::$paramsRepository = static::$entityManager->getRepository("ConcertoPanelBundle:TestWizardParam");
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
            "wizard" => 1,
            "wizardStep" => 1,
            "order" => 0,
            "hideCondition" => "",
            "serializedDefinition" => json_encode(array("placeholder" => 0))
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $content["result"]);
    }

    public function testWizardCollectionAction() {
        $client = self::createLoggedClient();

        $client->request('POST', '/admin/TestWizardStep/TestWizard/1/collection');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $expected = array(
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
        );
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));

        $client->request('POST', '/admin/TestWizardStep/TestWizard/2/collection');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $expected = array();
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testCollectionAction() {
        $client = self::createLoggedClient();

        $client->request('POST', '/admin/TestWizardStep/collection');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $expected = array(
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
        );
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testDeleteAction() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/TestWizardStep/1/delete");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array(
            "result" => 0,
            "object_ids" => 1
                ), json_decode($client->getResponse()->getContent(), true));
        $this->assertCount(0, self::$repository->findAll());
        $this->assertCount(0, self::$paramsRepository->findAll());
    }

    public function testClearAction() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/TestWizardStep/TestWizard/1/clear");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array(
            "result" => 0
                ), json_decode($client->getResponse()->getContent(), true));
        $this->assertCount(0, self::$repository->findAll());
        $this->assertCount(0, self::$paramsRepository->findAll());
    }

    public function testSaveActionNew() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/TestWizardStep/-1/save", array(
            "title" => "step2",
            "description" => "Second step",
            "orderNum" => "1",
            "wizard" => 1
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array(
            "result" => 0,
            "errors" => array(),
            "object" => array(
                "class_name" => "TestWizardStep",
                "id" => 2,
                "title" => "step2",
                "description" => "Second step",
                "orderNum" => "1",
                "wizard" => 1,
                "colsNum" => 0,
                "params" => array()
            )), json_decode($client->getResponse()->getContent(), true));
        $this->assertCount(2, self::$repository->findAll());
    }

    public function testSaveActionRename() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/TestWizardStep/1/save", array(
            "title" => "step UNO",
            "description" => "step UNO",
            "orderNum" => "1",
            "wizard" => 1
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array(
            "result" => 0,
            "errors" => array(),
            "object" => array(
                "class_name" => "TestWizardStep",
                "id" => 1,
                "title" => "step UNO",
                "description" => "step UNO",
                "orderNum" => "1",
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
                        "stepTitle" => "step UNO",
                        "order" => 0,
                        "value" => null,
                        "hideCondition" => "",
                        "definition" => array(
                            "placeholder" => 0
                        )
                    )
                )
            )), json_decode($client->getResponse()->getContent(), true));
        $this->assertCount(1, self::$repository->findAll());
    }

}
