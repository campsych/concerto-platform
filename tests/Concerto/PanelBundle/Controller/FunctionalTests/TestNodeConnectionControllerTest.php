<?php

namespace Tests\Concerto\PanelBundle\Controller\FunctionalTests;

use Tests\Concerto\PanelBundle\AFunctionalTest;
use Concerto\PanelBundle\Entity\ATopEntity;
use Concerto\PanelBundle\Entity\Test;

class TestNodeConnectionControllerTest extends AFunctionalTest
{

    private static $repository;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::$repository = static::$entityManager->getRepository("ConcertoPanelBundle:TestNodeConnection");
    }

    protected function setUp()
    {
        parent::setUp();

        $client = self::createLoggedClient();
        $client->request("POST", "/admin/Test/-1/save", array(
            "name" => "testFlow",
            "description" => "description",
            "code" => "print('start')",
            "visibility" => Test::VISIBILITY_REGULAR,
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
            "visibility" => Test::VISIBILITY_REGULAR,
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
            "visibility" => Test::VISIBILITY_REGULAR,
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

    public function testCollectionAction()
    {
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
                "defaultReturnFunction" => "0"
            )
        );
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testDeleteAction()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/TestNodeConnection/1/delete");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals([
            "result" => 0,
            "objectTimestamp" => $decodedResponse["objectTimestamp"]
        ], $decodedResponse);
        self::$repository->clear();
        $entity = self::$repository->find(1);
        $this->assertNull($entity);
    }

    public function testSaveActionNew()
    {
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
            "defaultReturnFunction" => "0"
        );

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(array(
            "result" => 0,
            "objectTimestamp" => $decodedResponse["objectTimestamp"],
            "errors" => array(),
            "object" => $expected
        ), $decodedResponse);
        $this->assertCount(2, self::$repository->findAll());
    }

    public function testSaveActionEdit()
    {
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
            "defaultReturnFunction" => "0"
        );

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(array(
            "result" => 0,
            "objectTimestamp" => $decodedResponse["objectTimestamp"],
            "errors" => array(),
            "object" => $expected
        ), $decodedResponse);
        $this->assertCount(1, self::$repository->findAll());
    }

}
