<?php

namespace Concerto\PanelBundle\Tests\Controller\FunctionalTests;

use Concerto\PanelBundle\Tests\AFunctionalTest;
use Concerto\PanelBundle\Entity\ATopEntity;
use Concerto\PanelBundle\Entity\TestSession;
use Concerto\PanelBundle\Entity\Test;
use Concerto\PanelBundle\Service\TestSessionService;

class TestSessionControllerTest extends AFunctionalTest {

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
        $this->assertEquals(1, $content["object_id"]);

        $session = new TestSession();
        $session->setTest(self::$testRepository->find(1));
        $session->setClientIp("192.168.0.100");
        $session->setClientBrowser("Gecko");
        $session->setRServerNodeId("local");
        $session->setTestServerNodeId("local");
        $session->setTestServerNodePort("8888");
        $session->setDebug(false);
        $session->setParams(json_encode(array()));
        $session->setHash(sha1("secret1"));
        $session->setStatus(TestSessionService::STATUS_RUNNING);
        self::$entityManager->persist($session);
        self::$entityManager->flush();
    }

    public function testStartNewActionUnauthorizedNode() {
        $client = self::createClient();
        $client->setServerParameter("REMOTE_ADDR", "192.168.0.2");
        $client->request("POST", "/TestSession/Test/1/start");
        $this->assertTrue($client->getResponse()->isSuccessful());

        $expected = array(
            "source" => TestSessionService::SOURCE_TEST_SERVER,
            "code" => TestSessionService::RESPONSE_AUTHENTICATION_FAILED
        );
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testSubmitActionUnauthorizedNode() {
        $client = self::createClient();
        $client->setServerParameter("REMOTE_ADDR", "192.168.0.2");
        $session = self::$repository->find(1);
        $client->request("POST", "/TestSession/" . $session->getHash() . "/submit");
        $this->assertTrue($client->getResponse()->isSuccessful());

        $expected = array(
            "source" => TestSessionService::SOURCE_TEST_SERVER,
            "code" => TestSessionService::RESPONSE_AUTHENTICATION_FAILED
        );
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testSubmitActionNotExistantSession() {
        $client = self::createClient();
        $client->setServerParameter("REMOTE_ADDR", "192.168.0.1");
        $client->request("POST", "/TestSession/abc123/submit", array(
            "r_server_node_hash" => "someHash"
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $expected = array(
            "source" => TestSessionService::SOURCE_TEST_SERVER,
            "code" => TestSessionService::RESPONSE_ERROR
        );
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testResumeActionUnauthorizedNode() {
        $client = self::createClient();
        $client->setServerParameter("REMOTE_ADDR", "192.168.0.2");
        $session = self::$repository->find(1);
        $client->request("POST", "/TestSession/" . $session->getHash() . "/resume");
        $this->assertTrue($client->getResponse()->isSuccessful());

        $expected = array(
            "source" => TestSessionService::SOURCE_TEST_SERVER,
            "code" => TestSessionService::RESPONSE_AUTHENTICATION_FAILED
        );
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testResumeActionNonExistantSession() {
        $client = self::createClient();
        $client->setServerParameter("REMOTE_ADDR", "192.168.0.1");
        $client->request("POST", "/TestSession/abc123/resume", array(
            "r_server_node_hash" => "someHash"
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $expected = array(
            "source" => TestSessionService::SOURCE_TEST_SERVER,
            "code" => TestSessionService::RESPONSE_ERROR
        );
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testResumeAction() {
        $client = self::createClient();
        $client->setServerParameter("REMOTE_ADDR", "192.168.0.1");

        $session = self::$repository->find(1);

        $client->request("POST", "/TestSession/" . $session->getHash() . "/resume", array(
            "r_server_node_hash" => "someHash"
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $expected = array(
            "source" => TestSessionService::SOURCE_TEST_SERVER,
            "code" => TestSessionService::RESPONSE_VIEW_TEMPLATE,
            "results" => $session->getReturns(),
            "timeLimit" => $session->getTimeLimit(),
            "hash" => $session->getHash(),
            "templateHead" => $session->getTemplateHead(),
            "templateCss" => $session->getTemplateCss(),
            "templateJs" => $session->getTemplateJs(),
            "templateHtml" => $session->getTemplateHtml(),
            "templateParams" => $session->getTemplateParams(),
            "loaderHead" => $session->getLoaderHead(),
            "loaderCss" => $session->getLoaderCss(),
            "loaderJs" => $session->getLoaderJs(),
            "loaderHtml" => $session->getLoaderHtml(),
            "isResumable" => false,
            "debug" => ""
        );
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testResultsActionUnauthorizedNode() {
        $client = self::createClient();
        $client->setServerParameter("REMOTE_ADDR", "192.168.0.2");
        $session = self::$repository->find(1);
        $client->request("POST", "/TestSession/" . $session->getHash() . "/results");
        $this->assertTrue($client->getResponse()->isSuccessful());

        $expected = array(
            "source" => TestSessionService::SOURCE_TEST_SERVER,
            "code" => TestSessionService::RESPONSE_AUTHENTICATION_FAILED
        );
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testResultsActionNonExistantSession() {
        $client = self::createClient();
        $client->setServerParameter("REMOTE_ADDR", "192.168.0.1");

        $client->request("POST", "/TestSession/abc123/results", array(
            "r_server_node_hash" => "someHash"
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $expected = array(
            "source" => TestSessionService::SOURCE_TEST_SERVER,
            "code" => TestSessionService::RESPONSE_ERROR
        );
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testResultsAction() {
        $client = self::createClient();
        $client->setServerParameter("REMOTE_ADDR", "192.168.0.1");

        $session = self::$repository->find(1);
        $client->request("POST", "/TestSession/" . $session->getHash() . "/results", array(
            "r_server_node_hash" => "someHash"
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $expected = array(
            "source" => TestSessionService::SOURCE_TEST_SERVER,
            "code" => TestSessionService::RESPONSE_RESULTS,
            "results" => $session->getReturns(),
            "debug" => ""
        );
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

}
