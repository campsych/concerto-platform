<?php

namespace Tests\Concerto\TestBundle\Controller\FunctionalTests;

use Tests\Concerto\PanelBundle\AFunctionalTest;
use Concerto\PanelBundle\Entity\ATopEntity;
use Concerto\PanelBundle\Entity\TestSession;
use Concerto\PanelBundle\Entity\Test;
use Concerto\PanelBundle\Service\TestSessionService;

class TestRunnerControllerTest extends AFunctionalTest {

    private static $repository;
    private static $testRepository;

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        self::$repository = static::$entityManager->getRepository("ConcertoPanelBundle:TestSession");
        self::$testRepository = static::$entityManager->getRepository("ConcertoPanelBundle:Test");
    }

    protected function setUp() {
        parent::setUp();

        $client = self::createLoggedClient();
        $client->request("POST", "/admin/Test/-1/save", array(
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

        $session = new TestSession();
        $session->setTest(self::$testRepository->find(1));
        $session->setClientIp("192.168.0.100");
        $session->setClientBrowser("Gecko");
        $session->setDebug(false);
        $session->setParams(json_encode(array()));
        $session->setHash(sha1("secret1"));
        $session->setStatus(TestSessionService::STATUS_RUNNING);
        self::$entityManager->persist($session);
        self::$entityManager->flush();
    }

    public function testSubmitActionNotExistantSession() {
        $client = self::createClient();
        $client->setServerParameter("REMOTE_ADDR", "192.168.0.1");
        $client->request("POST", "/test/session/abc123/submit", array(
            "test_node_hash" => "someHash"
        ));
        $fail_msg = "";
        if (!$client->getResponse()->isSuccessful()) {
            $crawler = $client->getCrawler();
            $fail_msg = $crawler->filter("title")->text();
        }
        $this->assertTrue($client->getResponse()->isSuccessful(), $fail_msg);

        $expected = array(
            "source" => TestSessionService::SOURCE_PANEL_NODE,
            "code" => TestSessionService::RESPONSE_ERROR
        );
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

}
