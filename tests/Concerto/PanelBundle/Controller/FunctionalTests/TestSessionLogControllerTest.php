<?php

namespace Tests\Concerto\PanelBundle\Controller\FunctionalTests;

use Tests\Concerto\PanelBundle\AFunctionalTest;
use Concerto\PanelBundle\Entity\ATopEntity;
use Concerto\PanelBundle\Entity\Test;
use Concerto\PanelBundle\Entity\TestSessionLog;

class TestSessionLogControllerTest extends AFunctionalTest {

    private static $repository;
    private static $testRepository;

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        self::$repository = static::$entityManager->getRepository("ConcertoPanelBundle:TestSessionLog");
        self::$testRepository = static::$entityManager->getRepository("ConcertoPanelBundle:Test");
    }

    protected function setUp() {
        parent::setUp();

        $client = self::createLoggedClient();
        $client->request("POST", "/admin/Test/-1/save", array(
            "class_name" => "TestSessionLog",
            "name" => "test",
            "description" => "description",
            "code" => "print('start')",
            "visibility" => Test::VISIBILITY_FEATURED,
            "type" => Test::TYPE_CODE,
            "accessibility" => ATopEntity::ACCESS_PUBLIC
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $content["result"]);

        $log = new TestSessionLog();
        $log->setBrowser("gecko");
        $log->setIp("192.168.0.1");
        $log->setMessage("error");
        $log->setTest(self::$testRepository->find(1));
        $log->setType(TestSessionLog::TYPE_R);
        self::$entityManager->persist($log);
        self::$entityManager->flush();
    }

    public function testCollectionAction() {
        $client = self::createLoggedClient();

        $log = self::$repository->find(1);

        $client->request('POST', '/admin/TestSessionLog/Test/1/collection');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $expected = array(
            array(
                "class_name" => "TestSessionLog",
                "id" => 1,
                "browser" => "gecko",
                "ip" => "192.168.0.1",
                "message" => "error",
                "type" => TestSessionLog::TYPE_R,
                "test_id" => 1,
                "created" => $log->getCreated()->format("Y-m-d H:i:s")
            )
        );
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));

        $client->request('POST', '/admin/TestSessionLog/Test/2/collection');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertCount(0, json_decode($client->getResponse()->getContent(), true));
    }

    public function testDeleteAction() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/TestSessionLog/1/delete");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array("result" => 0, "object_ids" => 1), json_decode($client->getResponse()->getContent(), true));
        self::$repository->clear();
        $entity = self::$repository->find(1);
        $this->assertNull($entity);
    }

    public function testClearAction() {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/TestSessionLog/Test/1/clear");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array("result" => 0), json_decode($client->getResponse()->getContent(), true));
        self::$repository->clear();
        $entity = self::$repository->find(1);
        $this->assertNull($entity);
    }

}
