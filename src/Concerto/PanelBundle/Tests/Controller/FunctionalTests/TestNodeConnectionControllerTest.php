<?php

namespace Concerto\PanelBundle\Tests\Controller\FunctionalTests;

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
        $this->assertEquals(1, $content["object_id"]);

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
        $this->assertEquals(2, $content["object_id"]);

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
        $this->assertEquals(3, $content["object_id"]);

        $client->request("POST", "/admin/TestVariable/-1/save", array(
            "name" => "response",
            "test" => 2,
            "type" => 1,
            "passableThroughUrl" => '0'
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $content["result"]);
        $this->assertEquals(4, $content["object_id"]);

        $client->request("POST", "/admin/TestVariable/-1/save", array(
            "name" => "params",
            "test" => 3,
            "type" => 0,
            "passableThroughUrl" => '0'
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $content["result"]);
        $this->assertEquals(5, $content["object_id"]);

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
        $this->assertEquals(3, $content["object_id"]);

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
        $this->assertEquals(4, $content["object_id"]);

        $client->request("POST", "/admin/TestNodeConnection/-1/save", array(
            "flowTest" => 1,
            "sourceNode" => 3,
            "destinationNode" => 4,
            "sourcePort" => 3,
            "destinationPort" => 5
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $content["result"]);
        $this->assertEquals(1, $content["object_id"]);
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
                "sourcePort" => 3,
                "destinationNode" => 4,
                "destinationPort" => 5,
                "returnFunction" => "out",
                "automatic" => 0,
                "sourcePortObject" => array(
                    "class_name" => "TestNodePort",
                    "id" => 3,
                    "value" => '0',
                    "node" => 3,
                    "string" => "1",
                    "defaultValue" => "1",
                    "variable" => 2,
                    "variableObject" => array(
                        "class_name" => "TestVariable",
                        "id" => 2,
                        "name" => "out",
                        "type" => 2,
                        "description" => "",
                        "passableThroughUrl" => '0',
                        "value" => '0',
                        "test" => 2,
                        "parentVariable" => null
                    )
                ),
                "destinationPortObject" => array(
                    "class_name" => "TestNodePort",
                    "id" => 5,
                    "value" => '0',
                    "node" => 4,
                    "string" => "1",
                    "defaultValue" => "1",
                    "variable" => 3,
                    "variableObject" => array(
                        "class_name" => "TestVariable",
                        "id" => 3,
                        "name" => "out",
                        "type" => 2,
                        "description" => "",
                        "passableThroughUrl" => '0',
                        "value" => '0',
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
            "sourcePort" => 4,
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
            "sourcePort" => 4,
            "destinationNode" => 4,
            "destinationPort" => null,
            "returnFunction" => "params",
            "automatic" => 0,
            "sourcePortObject" => array(
                "class_name" => "TestNodePort",
                "id" => 4,
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
            ),
            "destinationPortObject" => null
        );

        $this->assertEquals(array(
            "result" => 0,
            "errors" => array(),
            "object_id" => 2,
            "object" => $expected
                ), json_decode($client->getResponse()->getContent(), true));
        $this->assertCount(2, self::$repository->findAll());
    }

    public function testSaveActionEdit() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/TestNodeConnection/1/save", array(
            "flowTest" => 1,
            "sourceNode" => 3,
            "sourcePort" => 4,
            "destinationNode" => 4,
            "destinationPort" => null,
            "returnFunction" => "",
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $expected = array(
            "class_name" => "TestNodeConnection",
            "id" => 1,
            "flowTest" => 1,
            "sourceNode" => 3,
            "sourcePort" => 4,
            "destinationNode" => 4,
            "destinationPort" => null,
            "returnFunction" => "params",
            "automatic" => 0,
            "sourcePortObject" => array(
                "class_name" => "TestNodePort",
                "id" => 4,
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
            ),
            "destinationPortObject" => null
        );

        $this->assertEquals(array(
            "result" => 0,
            "errors" => array(),
            "object_id" => 1,
            "object" => $expected
                ), json_decode($client->getResponse()->getContent(), true));
        $this->assertCount(1, self::$repository->findAll());
    }

}
