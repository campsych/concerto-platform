<?php

namespace Tests\Concerto\PanelBundle\Controller\FunctionalTests;

use Tests\Concerto\PanelBundle\AFunctionalTest;
use Concerto\PanelBundle\Entity\ATopEntity;
use Concerto\PanelBundle\Entity\Test;

class TestNodePortControllerTest extends AFunctionalTest {

    private static $repository;

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        self::$repository = static::$entityManager->getRepository("ConcertoPanelBundle:TestNodePort");
    }

    protected function setUp() {
        self::truncateClass("ConcertoPanelBundle:Test");
        self::truncateClass("ConcertoPanelBundle:TestVariable");
        self::truncateClass("ConcertoPanelBundle:TestNode");
        self::truncateClass("ConcertoPanelBundle:TestNodePort");
        self::truncateClass("ConcertoPanelBundle:TestNodeConnection");

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
    }

    public function testCollectionAction() {
        $client = self::createLoggedClient();

        $client->request('POST', '/admin/TestNodePort/collection');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $expected = array(
            array(
                "class_name" => "TestNodePort",
                "id" => 1,
                "node" => 3,
                "string" => "1",
                "defaultValue" => "1",
                "variable" => 2,
                "value" => "0",
                "variableObject" => array(
                    "class_name" => "TestVariable",
                    "id" => 2,
                    "name" => "out",
                    "type" => 2,
                    "description" => "",
                    "passableThroughUrl" => "0",
                    "value" => '0',
                    "test" => 2,
                    "parentVariable" => null
                )
            )
        );
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testDeleteAction() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/TestNodePort/1/delete");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array("result" => 0, "object_ids" => 1), json_decode($client->getResponse()->getContent(), true));
        self::$repository->clear();
        $entity = self::$repository->find(1);
        $this->assertNull($entity);
    }

    public function testSaveActionEdit() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/TestNodePort/1/save", array(
            "node" => 1,
            "variable" => 2,
            "value" => "1",
            "string" => "0",
            "default" => "0"
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $expected = array(
            "class_name" => "TestNodePort",
            "id" => 1,
            "node" => 1,
            "string" => "0",
            "defaultValue" => "0",
            "variable" => 2,
            "value" => "1",
            "variableObject" => array(
                "class_name" => "TestVariable",
                "id" => 2,
                "name" => "out",
                "type" => 2,
                "description" => "",
                "passableThroughUrl" => "0",
                "value" => '0',
                "test" => 2,
                "parentVariable" => null
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
