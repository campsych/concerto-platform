<?php

namespace Concerto\PanelBundle\Tests\Controller\FunctionalTests;

use Concerto\PanelBundle\Entity\Test;
use Concerto\PanelBundle\Entity\AEntity;

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
            "visibility" => Test::VISIBILITY_FEATURED,
            "type" => Test::TYPE_CODE,
            "accessibility" => AEntity::ACCESS_PUBLIC
        ));
        $client->request("POST", "/admin/Test/-1/save", array(
            "name" => "test2",
            "visibility" => Test::VISIBILITY_SUBTEST,
            "type" => Test::TYPE_CODE,
            "accessibility" => AEntity::ACCESS_PUBLIC
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
                "resumable" => '0',
                "visibility" => Test::VISIBILITY_FEATURED,
                'variables' => json_decode($client->getResponse()->getContent(), true)[0]['variables'],
                'logs' => array(),
                'sourceWizard' => null,
                'sourceWizardObject' => null,
                "updatedOn" => json_decode($client->getResponse()->getContent(), true)[0]['updatedOn'],
                "updatedByName" => 'admin',
                "slug" => json_decode($client->getResponse()->getContent(), true)[0]['slug'],
                "outdated" => '0',
                "accessibility" => 0,
                "protected" => 0,
                "archived" => 0,
                "globalId" => null,
                "revision" => 0,
                "checksum" => "",
                "starterContent" => false,
                "owner" => null,
                "groups" => "",
                "type" => 0,
                "nodes" => array(),
                "nodesConnections" => array(),
                "tags" => ""
            )
        );
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

}
