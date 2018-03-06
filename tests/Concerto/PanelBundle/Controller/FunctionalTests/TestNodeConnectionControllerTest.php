<?php

namespace Tests\Concerto\PanelBundle\Controller\FunctionalTests;

use Tests\Concerto\PanelBundle\AFunctionalTest;
use Concerto\PanelBundle\Entity\ATopEntity;
use Concerto\PanelBundle\Entity\Test;

class TestNodeConnectionControllerTest extends AFunctionalTest {

    private static $repository;

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        self::$repository = static::$entityManager->getRepository("ConcertoPanelBundle:TestNodeConnection");
    }

    protected function setUp() {
        parent::setUp();

        $client = self::createLoggedClient();
        $client->request("POST", "/admin/Test/-1/save", array(
            "name" => "testFlow",
            "description" => "description",
            "code" => "print('start')",
            "visibility" => Test::VISIBILITY_FEATURED,
            "type" => Test::TYPE_FLOW,
            "accessibility" => ATopEntity::ACCESS_PUBLIC
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $content["result"]);

        $client->request("POST", "/admin/Test/-1/save", array(
            "name" => "test_s1",
            "description" => "description",
            "code" => "print('start')",
            "visibility" => Test::VISIBILITY_FEATURED,
            "type" => Test::TYPE_CODE,
            "accessibility" => ATopEntity::ACCESS_PUBLIC
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $content["result"]);

        $client->request("POST", "/admin/Test/-1/save", array(
            "name" => "test_s2",
            "description" => "description",
            "code" => "print('start')",
            "visibility" => Test::VISIBILITY_FEATURED,
            "type" => Test::TYPE_CODE,
            "accessibility" => ATopEntity::ACCESS_PUBLIC
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $content["result"]);

        $client->request("POST", "/admin/TestVariable/-1/save", array(
            "name" => "response",
            "test" => 2,
            "type" => 1,
            "passableThroughUrl" => '0'
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $content["result"]);

        $client->request("POST", "/admin/TestVariable/-1/save", array(
            "name" => "params",
            "test" => 3,
            "type" => 0,
            "passableThroughUrl" => '0'
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $content["result"]);

        $client->request("POST", "/admin/TestNode/-1/save", array(
            "flowTest" => 1,
            "sourceTest" => 2,
            "type" => 0,
            "posX" => 0,
            "posY" => 0,
            "title" => ""
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $content["result"]);

        $client->request("POST", "/admin/TestNode/-1/save", array(
            "flowTest" => 1,
            "sourceTest" => 3,
            "type" => 0,
            "posX" => 1,
            "posY" => 1,
            "title" => ""
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $content["result"]);

        $client->request("POST", "/admin/TestNodeConnection/-1/save", array(
            "flowTest" => 1,
            "sourceNode" => 3,
            "destinationNode" => 4,
            "sourcePort" => 1,
            "destinationPort" => 3
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $content["result"]);
    }

    public function testCollectionAction() {
        $client = self::createLoggedClient();

        $client->request('POST', '/admin/TestNodeConnection/collection');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $expected = array(
            array(
                "class_name" => "TestNodeConnection",
                "id" => 1,
                "flowTest" => 1,
                "sourceNode" => 3,
                "sourcePort" => 1,
                "destinationNode" => 4,
                "destinationPort" => 3,
                "returnFunction" => "response",
                "defaultReturnFunction" => "0",
                "automatic" => "0",
                "sourcePortObject" => array(
                    "class_name" => "TestNodePort",
                    "id" => 1,
                    "value" => null,
                    "node" => 3,
                    "string" => "1",
                    "defaultValue" => "1",
                    "variable" => 4,
                    "variableObject" => array(
                        "class_name" => "TestVariable",
                        "id" => 4,
                        "name" => "response",
                        "type" => 1,
                        "description" => "",
                        "passableThroughUrl" => '0',
                        "value" => null,
                        "test" => 2,
                        "parentVariable" => null
                    )
                ),
                "destinationPortObject" => array(
                    "class_name" => "TestNodePort",
                    "id" => 3,
                    "value" => null,
                    "node" => 4,
                    "string" => "1",
                    "defaultValue" => "1",
                    "variable" => 5,
                    "variableObject" => array(
                        "class_name" => "TestVariable",
                        "id" => 5,
                        "name" => "params",
                        "type" => 0,
                        "description" => "",
                        "passableThroughUrl" => '0',
                        "value" => null,
                        "test" => 3,
                        "parentVariable" => null
                    )
                )
            )
        );
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testDeleteAction() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/TestNodeConnection/1/delete");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array("result" => 0, "object_ids" => 1), json_decode($client->getResponse()->getContent(), true));
        self::$repository->clear();
        $entity = self::$repository->find(1);
        $this->assertNull($entity);
    }

    public function testSaveActionNew() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/TestNodeConnection/-1/save", array(
            "flowTest" => 1,
            "sourceNode" => 3,
            "sourcePort" => null,
            "destinationNode" => 4,
            "destinationPort" => null,
            "returnFunction" => "",
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $expected = array(
            "class_name" => "TestNodeConnection",
            "id" => 2,
            "flowTest" => 1,
            "sourceNode" => 3,
            "sourcePort" => null,
            "destinationNode" => 4,
            "destinationPort" => null,
            "returnFunction" => "",
            "defaultReturnFunction" => "0",
            "automatic" => "0",
            "sourcePortObject" => null,
            "destinationPortObject" => null,
        );

        $this->assertEquals(array(
            "result" => 0,
            "errors" => array(),
            "object" => $expected
                ), json_decode($client->getResponse()->getContent(), true));
        $this->assertCount(2, self::$repository->findAll());
    }

    public function testSaveActionEdit() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/TestNodeConnection/1/save", array(
            "flowTest" => 1,
            "sourceNode" => 3,
            "sourcePort" => 1,
            "destinationNode" => 4,
            "destinationPort" => 3,
            "returnFunction" => "zzz",
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $expected = array(
            "class_name" => "TestNodeConnection",
            "id" => 1,
            "flowTest" => 1,
            "sourceNode" => 3,
            "sourcePort" => 1,
            "destinationNode" => 4,
            "destinationPort" => 3,
            "returnFunction" => "zzz",
            "defaultReturnFunction" => "0",
            "automatic" => "0",
            "sourcePortObject" => array(
                "class_name" => "TestNodePort",
                "id" => 1,
                "value" => null,
                "node" => 3,
                "string" => "1",
                "defaultValue" => "1",
                "variable" => 4,
                "variableObject" => array(
                    "class_name" => "TestVariable",
                    "id" => 4,
                    "name" => "response",
                    "type" => 1,
                    "description" => "",
                    "passableThroughUrl" => '0',
                    "value" => null,
                    "test" => 2,
                    "parentVariable" => null
                )
            ),
            "destinationPortObject" => array(
                "class_name" => "TestNodePort",
                "id" => 3,
                "value" => null,
                "node" => 4,
                "string" => "1",
                "defaultValue" => "1",
                "variable" => 5,
                "variableObject" => array(
                    "class_name" => "TestVariable",
                    "id" => 5,
                    "name" => "params",
                    "type" => 0,
                    "description" => "",
                    "passableThroughUrl" => '0',
                    "value" => null,
                    "test" => 3,
                    "parentVariable" => null
                )
            )
        );

        $this->assertEquals(array(
            "result" => 0,
            "errors" => array(),
            "object" => $expected
                ), json_decode($client->getResponse()->getContent(), true));
        $this->assertCount(1, self::$repository->findAll());
    }

}
