<?php

namespace Tests\Concerto\PanelBundle\Controller\FunctionalTests;

use Tests\Concerto\PanelBundle\AFunctionalTest;
use Concerto\PanelBundle\Entity\Test;
use Concerto\PanelBundle\Entity\ATopEntity;

class HomeControllerTest extends AFunctionalTest {

    private static $repository;

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        self::$repository = static::$entityManager->getRepository("ConcertoPanelBundle:Test");
    }

    protected function setUp() {
        self::truncateClass("ConcertoPanelBundle:Test");
        self::truncateClass("ConcertoPanelBundle:TestSession");

        $client = self::createLoggedClient();
        $client->request("POST", "/admin/Test/-1/save", array(
            "name" => "test1",
            "code" => "",
            "visibility" => Test::VISIBILITY_FEATURED,
            "type" => Test::TYPE_CODE,
            "accessibility" => ATopEntity::ACCESS_PUBLIC
        ));
        $client->request("POST", "/admin/Test/-1/save", array(
            "name" => "test2",
            "code" => "",
            "visibility" => Test::VISIBILITY_SUBTEST,
            "type" => Test::TYPE_CODE,
            "accessibility" => ATopEntity::ACCESS_PUBLIC
        ));
    }

    public function testIndexAction() {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($crawler->filter('html:contains("Available tests")')->count() > 0);
    }

    public function testFeaturedCollectionAction() {
        $client = static::createClient();

        $client->request('GET', '/featured/collection');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $expected = array(
            array(
                "class_name" => "Test",
                "id" => 1,
                "name" => "test1",
                "description" => "",
                "code" => "",
                "visibility" => Test::VISIBILITY_FEATURED,
                'variables' => json_decode($client->getResponse()->getContent(), true)[0]['variables'],
                'sourceWizard' => null,
                'sourceWizardName' => null,
                'sourceWizardTest' => null,
                'sourceWizardTestName' => null,
                "updatedOn" => json_decode($client->getResponse()->getContent(), true)[0]['updatedOn'],
                "updatedBy" => 'admin',
                "slug" => json_decode($client->getResponse()->getContent(), true)[0]['slug'],
                "accessibility" => ATopEntity::ACCESS_PUBLIC,
                "archived" => '0',
                "starterContent" => false,
                "owner" => null,
                "groups" => "",
                "type" => 0,
                "nodes" => array(),
                "nodesConnections" => array(),
                "tags" => "",
                "steps" => array(),
                "lockedBy" => null,
                "directLockBy" => null,
                "baseTemplate" => null
            )
        );
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

}
