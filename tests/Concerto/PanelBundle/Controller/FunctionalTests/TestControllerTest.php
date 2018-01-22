<?php

namespace Tests\Concerto\PanelBundle\Controller\FunctionalTests;

use Tests\Concerto\PanelBundle\AFunctionalTest;
use Concerto\PanelBundle\Entity\ATopEntity;
use Concerto\PanelBundle\Entity\Test;

class TestControllerTest extends AFunctionalTest
{

    private static $repository;
    private static $varRepository;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::$repository = static::$entityManager->getRepository("ConcertoPanelBundle:Test");
        self::$varRepository = static::$entityManager->getRepository("ConcertoPanelBundle:TestVariable");
    }

    protected function setUp()
    {
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

        //HTTP response
        $fail_msg = "";
        if (!$client->getResponse()->isSuccessful()) {
            $crawler = $client->getCrawler();
            $fail_msg = $crawler->filter("title")->text();
        }
        $this->assertTrue($client->getResponse()->isSuccessful(), $fail_msg);
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $content["result"]);
    }

    public function testCollectionAction()
    {
        $client = self::createLoggedClient();

        $client->request('POST', '/admin/Test/collection');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $expected = array(
            array(
                "class_name" => "Test",
                "id" => 1,
                "name" => "test",
                "description" => "description",
                "code" => "print('start')",
                "visibility" => Test::VISIBILITY_FEATURED,
                'variables' => array(
                    array(
                        "class_name" => "TestVariable",
                        "id" => 1,
                        "name" => "out",
                        "type" => 2,
                        "description" => "",
                        "passableThroughUrl" => "0",
                        "value" => "0",
                        "test" => 1,
                        "parentVariable" => null
                    )
                ),
                'logs' => array(),
                'sourceWizard' => null,
                'sourceWizardName' => null,
                'sourceWizardTest' => null,
                'sourceWizardTestName' => null,
                "updatedOn" => json_decode($client->getResponse()->getContent(), true)[0]['updatedOn'],
                "updatedBy" => "admin",
                "slug" => json_decode($client->getResponse()->getContent(), true)[0]['slug'],
                "outdated" => '0',
                "type" => Test::TYPE_CODE,
                "accessibility" => ATopEntity::ACCESS_PUBLIC,
                "archived" => "0",
                "starterContent" => false,
                "owner" => null,
                "groups" => "",
                "nodes" => array(),
                "nodesConnections" => array(),
                "tags" => "",
                "steps" => array()
            )
        );
        $this->assertEquals($expected, json_decode($client->getResponse()->getContent(), true));
    }

    public function testFormActionNew()
    {
        $client = self::createLoggedClient();

        $crawler = $client->request("POST", "/admin/Test/form/add");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertGreaterThan(0, $crawler->filter("input[type='text'][ng-model='object.name']")->count());
        $this->assertGreaterThan(0, $crawler->filter("select[ng-model='object.visibility']")->count());
    }

    public function testFormActionEdit()
    {
        $client = self::createLoggedClient();

        $crawler = $client->request("POST", "/admin/Test/form/edit");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertGreaterThan(0, $crawler->filter("html:contains('Error logs')")->count());
        $this->assertGreaterThan(0, $crawler->filter("html:contains('Test input')")->count());
        $this->assertGreaterThan(0, $crawler->filter("html:contains('Test logic')")->count());
        $this->assertGreaterThan(0, $crawler->filter("html:contains('Test output')")->count());
        $this->assertGreaterThan(0, $crawler->filter("input[type='text'][ng-model='object.name']")->count());
        $this->assertGreaterThan(0, $crawler->filter("select[ng-model='object.visibility']")->count());
    }

    public function testDeleteAction()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/Test/1/delete");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array("result" => 0, "object_ids" => 1), json_decode($client->getResponse()->getContent(), true));
        $this->assertCount(0, self::$repository->findAll());
        $this->assertCount(0, self::$varRepository->findAll());
    }

    /**
     * @dataProvider exportDataProvider
     */
    public function testExportAction($path_suffix, $use_gzip)
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/Test/1/export" . $path_suffix);
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/x-download'));

        $content = json_decode(
            ($use_gzip) ? gzuncompress($client->getResponse()->getContent()) : $client->getResponse()->getContent(), true
        );
        $this->assertArrayHasKey("hash", $content["collection"][0]);
        unset($content["collection"][0]["hash"]);

        $this->assertEquals(array(array(
            'class_name' => 'Test',
            'id' => 1,
            "starterContent" => false,
            'name' => 'test',
            'description' => 'description',
            'visibility' => 1,
            'code' => 'print(\'start\')',
            'accessibility' => ATopEntity::ACCESS_PUBLIC,
            "archived" => "0",
            "owner" => null,
            "groups" => "",
            'outdated' => '0',
            'sourceWizard' => null,
            'sourceWizardName' => null,
            'sourceWizardTest' => null,
            'sourceWizardTestName' => null,
            'updatedOn' => $content["collection"][0]["updatedOn"],
            'updatedBy' => 'admin',
            'type' => Test::TYPE_CODE,
            'variables' => array(
                array(
                    "class_name" => "TestVariable",
                    "id" => 1,
                    "name" => "out",
                    "type" => 2,
                    "description" => "",
                    "passableThroughUrl" => "0",
                    "value" => "0",
                    "test" => 1,
                    "parentVariable" => null
                )
            ),
            'nodes' => array(),
            'nodesConnections' => array(),
            "tags" => ""
        )), $content["collection"]);
    }

    public function testImportNewAction()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/Test/import", array(
            "file" => "Test_1.concerto.json",
            "instructions" => json_encode(array(
                array(
                    "class_name" => "Test",
                    "id" => 1,
                    "rename" => "imported_test",
                    "action" => "0",
                    "starter_content" => false,
                    "existing_object" => false,
                    "existing_object_name" => "null"
                )
            ))
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $new_entity = self::$repository->find(2);
        $this->assertNotNull($new_entity);
        $decoded_response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $decoded_response["result"]);
    }

    public function testImportNewSameNameAction()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/Test/import", array(
            "file" => "Test_1.concerto.json",
            "instructions" => json_encode(array(
                array(
                    "class_name" => "Test",
                    "id" => 1,
                    "rename" => "test",
                    "action" => "0",
                    "starter_content" => false,
                    "existing_object" => true,
                    "existing_object_name" => "test"
                )
            ))
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertCount(2, self::$repository->findAll());
        $decoded_response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $decoded_response["result"]);
        $this->assertCount(1, self::$repository->findBy(array("name" => "test_1")));
    }

    public function testSaveActionNew()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/Test/-1/save", array(
            "name" => "new_test",
            "visibility" => Test::VISIBILITY_FEATURED,
            "type" => Test::TYPE_CODE,
            "accessibility" => ATopEntity::ACCESS_PUBLIC
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array(
            "result" => 0,
            "errors" => array(),
            "object" => array(
                "class_name" => "Test",
                "id" => 2,
                "name" => "new_test",
                "description" => "",
                "code" => "",
                "visibility" => Test::VISIBILITY_FEATURED,
                'variables' => array(),
                'logs' => array(),
                'sourceWizard' => null,
                'sourceWizardName' => null,
                'sourceWizardTest' => null,
                'sourceWizardTestName' => null,
                "updatedOn" => json_decode($client->getResponse()->getContent(), true)["object"]['updatedOn'],
                "updatedBy" => 'admin',
                "slug" => json_decode($client->getResponse()->getContent(), true)["object"]['slug'],
                "outdated" => '0',
                "type" => Test::TYPE_CODE,
                "accessibility" => ATopEntity::ACCESS_PUBLIC,
                "archived" => "0",
                "starterContent" => false,
                "owner" => null,
                "groups" => "",
                "nodes" => array(),
                "nodesConnections" => array(),
                "variables" => array(
                    array(
                        "class_name" => "TestVariable",
                        "id" => 2,
                        "name" => "out",
                        "type" => 2,
                        "description" => "",
                        "passableThroughUrl" => '0',
                        "value" => 0,
                        "test" => 2,
                        "parentVariable" => null
                    )
                ),
                "tags" => "",
                "steps" => array()
            )), json_decode($client->getResponse()->getContent(), true));
        $this->assertCount(2, self::$repository->findAll());
    }

    public function testSaveActionRename()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/Test/1/save", array(
            "name" => "edited_test",
            "description" => "edited test description",
            "visibility" => Test::VISIBILITY_FEATURED,
            "code" => "code",
            "type" => Test::TYPE_CODE,
            "accessibility" => ATopEntity::ACCESS_PUBLIC
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array(
            "result" => 0,
            "errors" => array(),
            "object" => array(
                "class_name" => "Test",
                "id" => 1,
                "name" => "edited_test",
                "description" => "edited test description",
                "code" => "code",
                "visibility" => Test::VISIBILITY_FEATURED,
                'variables' => array(),
                'logs' => array(),
                'sourceWizard' => null,
                'sourceWizardName' => null,
                'sourceWizardTest' => null,
                'sourceWizardTestName' => null,
                "updatedOn" => json_decode($client->getResponse()->getContent(), true)["object"]['updatedOn'],
                "updatedBy" => 'admin',
                "slug" => json_decode($client->getResponse()->getContent(), true)["object"]['slug'],
                "outdated" => '0',
                "type" => Test::TYPE_CODE,
                "accessibility" => ATopEntity::ACCESS_PUBLIC,
                "archived" => "0",
                "starterContent" => false,
                "owner" => null,
                "groups" => "",
                "nodes" => array(),
                "nodesConnections" => array(),
                "variables" => array(
                    array(
                        "class_name" => "TestVariable",
                        "id" => 1,
                        "name" => "out",
                        "type" => 2,
                        "description" => "",
                        "passableThroughUrl" => 0,
                        "value" => 0,
                        "test" => 1,
                        "parentVariable" => null
                    )
                ),
                "tags" => "",
                "steps" => array()
            )), json_decode($client->getResponse()->getContent(), true));
        $this->assertCount(1, self::$repository->findAll());
    }

    public function testSaveActionSameName()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/Test/1/save", array(
            "name" => "test",
            "description" => "edited test description",
            "visibility" => Test::VISIBILITY_FEATURED,
            "code" => "code",
            "accessibility" => ATopEntity::ACCESS_PUBLIC));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array(
            "result" => 0,
            "errors" => array(),
            "object" => array(
                "class_name" => "Test",
                "id" => 1,
                "name" => "test",
                "description" => "edited test description",
                "code" => "code",
                "visibility" => Test::VISIBILITY_FEATURED,
                'variables' => array(),
                'logs' => array(),
                'sourceWizard' => null,
                'sourceWizardName' => null,
                'sourceWizardTest' => null,
                'sourceWizardTestName' => null,
                "updatedOn" => json_decode($client->getResponse()->getContent(), true)["object"]['updatedOn'],
                "updatedBy" => 'admin',
                "slug" => json_decode($client->getResponse()->getContent(), true)["object"]['slug'],
                "outdated" => '0',
                "type" => Test::TYPE_CODE,
                "accessibility" => ATopEntity::ACCESS_PUBLIC,
                "archived" => "0",
                "starterContent" => false,
                "owner" => null,
                "groups" => "",
                "nodes" => array(),
                "nodesConnections" => array(),
                "variables" => array(
                    array(
                        "class_name" => "TestVariable",
                        "id" => 1,
                        "name" => "out",
                        "type" => 2,
                        "description" => "",
                        "passableThroughUrl" => 0,
                        "value" => 0,
                        "test" => 1,
                        "parentVariable" => null
                    )
                ),
                "tags" => "",
                "steps" => array()
            )), json_decode($client->getResponse()->getContent(), true));
        $this->assertCount(1, self::$repository->findAll());
    }

    public function testSaveActionNameAlreadyExists()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/Test/-1/save", array(
            "name" => "new_test",
            "visibility" => Test::VISIBILITY_FEATURED,
            "type" => Test::TYPE_CODE,
            "accessibility" => ATopEntity::ACCESS_PUBLIC
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array(
            "result" => 0,
            "errors" => array(),
            "object" => array(
                "class_name" => "Test",
                "id" => 2,
                "name" => "new_test",
                "description" => "",
                "code" => "",
                "visibility" => Test::VISIBILITY_FEATURED,
                'variables' => array(),
                'logs' => array(),
                'sourceWizard' => null,
                'sourceWizardName' => null,
                'sourceWizardTest' => null,
                'sourceWizardTestName' => null,
                "updatedOn" => json_decode($client->getResponse()->getContent(), true)["object"]['updatedOn'],
                "updatedBy" => 'admin',
                "slug" => json_decode($client->getResponse()->getContent(), true)["object"]['slug'],
                "outdated" => '0',
                "type" => Test::TYPE_CODE,
                "accessibility" => ATopEntity::ACCESS_PUBLIC,
                "archived" => "0",
                "starterContent" => false,
                "owner" => null,
                "groups" => "",
                "nodes" => array(),
                "nodesConnections" => array(),
                "variables" => array(
                    array(
                        "class_name" => "TestVariable",
                        "id" => 2,
                        "name" => "out",
                        "type" => 2,
                        "description" => "",
                        "passableThroughUrl" => '0',
                        "value" => 0,
                        "test" => 2,
                        "parentVariable" => null
                    )
                ),
                "tags" => "",
                "steps" => array()
            )
        ), json_decode($client->getResponse()->getContent(), true));
        $this->assertCount(2, self::$repository->findAll());

        $client->request("POST", "/admin/Test/1/save", array(
            "name" => "new_test",
            "description" => "edited test description",
            "visibility" => Test::VISIBILITY_FEATURED,
            "code" => "code"));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertEquals(array(
            "result" => 1,
            "object" => null,
            "errors" => array("This name already exists in the system")
        ), json_decode($client->getResponse()->getContent(), true));
        $this->assertCount(2, self::$repository->findAll());
    }

    public function exportDataProvider()
    {
        return array(
            array('', true), // default is gzipped 
            array('/compressed', true), // explicitly requesting compression
            array('/plaintext', false)    // requesting plaintext
        );
    }

    public function testUpdateDependentAction()
    {
        $client = self::createLoggedClient();
        $client->request("POST", "/admin/TestWizard/-1/save", array(
            "name" => "wizard",
            "description" => "description",
            "accessibility" => ATopEntity::ACCESS_PUBLIC,
            "test" => 1
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $content["result"]);

        $client->request("POST", "/admin/Test/-1/save", array(
            "name" => "test2",
            "description" => "description",
            "code" => "print('start')",
            "visibility" => Test::VISIBILITY_FEATURED,
            "type" => Test::TYPE_WIZARD,
            "sourceWizard" => 1,
            "accessibility" => ATopEntity::ACCESS_PUBLIC
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $content["result"]);

        $client->request("POST", "/admin/Test/1/save", array(
            "name" => "wizard test",
            "description" => "description",
            "visibility" => Test::VISIBILITY_FEATURED,
            "code" => "aaa",
            "type" => Test::TYPE_CODE,
            "accessibility" => ATopEntity::ACCESS_PUBLIC
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $content["result"]);

        $client->request("POST", "/admin/Test/1/update");
        $fail_msg = "";
        if (!$client->getResponse()->isSuccessful()) {
            $crawler = $client->getCrawler();
            $fail_msg = $crawler->filter("title")->text();
        }
        $this->assertTrue($client->getResponse()->isSuccessful(), $fail_msg);
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        self::$entityManager->clear();
        $this->assertEquals(self::$repository->find(1)->getCode(), self::$repository->find(2)->getCode());
    }

}
