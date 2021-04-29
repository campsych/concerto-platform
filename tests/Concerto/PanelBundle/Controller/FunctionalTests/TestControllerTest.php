<?php

namespace Tests\Concerto\PanelBundle\Controller\FunctionalTests;

use Symfony\Component\Yaml\Yaml;
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
            "visibility" => Test::VISIBILITY_REGULAR,
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
                "visibility" => Test::VISIBILITY_REGULAR,
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
                'sourceWizard' => null,
                'sourceWizardName' => null,
                'sourceWizardTest' => null,
                'sourceWizardTestName' => null,
                "updatedOn" => json_decode($client->getResponse()->getContent(), true)[0]['updatedOn'],
                "updatedBy" => "admin",
                "slug" => json_decode($client->getResponse()->getContent(), true)[0]['slug'],
                "type" => Test::TYPE_CODE,
                "accessibility" => ATopEntity::ACCESS_PUBLIC,
                "archived" => "0",
                "protected" => "0",
                "starterContent" => false,
                "owner" => null,
                "groups" => "",
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

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals([
            "result" => 0,
            "objectTimestamp" => $decodedResponse["objectTimestamp"]
        ], $decodedResponse);
        $this->assertCount(0, self::$repository->findAll());
        $this->assertCount(0, self::$varRepository->findAll());
    }

    /**
     * @dataProvider exportDataProvider
     */
    public function testExportAction($instructions, $format)
    {
        $client = self::createLoggedClient();
        $encodedInstructions = json_encode($instructions);

        $client->request("GET", "/admin/Test/$encodedInstructions/export/$format");
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/x-download'));

        $content = null;
        switch ($format) {
            case "yml":
                $content = Yaml::parse($client->getResponse()->getContent());
                break;
            case "json":
                $content = json_decode($client->getResponse()->getContent(), true);
                break;
            case "compressed":
                $content = json_decode(gzuncompress($client->getResponse()->getContent()), true);
                break;
        }

        $this->assertArrayHasKey("hash", $content["collection"][0]);
        unset($content["collection"][0]["hash"]);

        $this->assertEquals(array(array(
            'class_name' => 'Test',
            'id' => 1,
            "starterContent" => false,
            'name' => 'test',
            'description' => 'description',
            'visibility' => Test::VISIBILITY_REGULAR,
            'code' => 'print(\'start\')',
            'accessibility' => ATopEntity::ACCESS_PUBLIC,
            "archived" => "0",
            "protected" => "0",
            "groups" => "",
            'sourceWizard' => null,
            'sourceWizardTest' => null,
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
            "tags" => "",
            "baseTemplate" => null
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
                    "name" => "subtest",
                    "rename" => "imported_test",
                    "action" => "0",
                    "starter_content" => false,
                    "existing_object" => false,
                    "existing_object_name" => "null"
                )
            )),
            "instant" => 1
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
                    "name" => "subtest",
                    "rename" => "test",
                    "action" => "0",
                    "starter_content" => false,
                    "existing_object" => true,
                    "existing_object_name" => "test"
                )
            )),
            "instant" => 1
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
            "visibility" => Test::VISIBILITY_REGULAR,
            "type" => Test::TYPE_CODE,
            "accessibility" => ATopEntity::ACCESS_PUBLIC,
            "code" => ""
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $this->assertCount(2, self::$repository->findAll());

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(array(
            "result" => 0,
            "errors" => array(),
            "objectTimestamp" => $decodedResponse["objectTimestamp"],
            "object" => array(
                "class_name" => "Test",
                "id" => 2,
                "name" => "new_test",
                "description" => "",
                "code" => "",
                "visibility" => Test::VISIBILITY_REGULAR,
                'variables' => array(),
                'sourceWizard' => null,
                'sourceWizardName' => null,
                'sourceWizardTest' => null,
                'sourceWizardTestName' => null,
                "updatedOn" => $decodedResponse["object"]['updatedOn'],
                "updatedBy" => 'admin',
                "slug" => $decodedResponse["object"]['slug'],
                "type" => Test::TYPE_CODE,
                "accessibility" => ATopEntity::ACCESS_PUBLIC,
                "archived" => "0",
                "protected" => "0",
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
                "steps" => array(),
                "lockedBy" => null,
                "directLockBy" => null,
                "baseTemplate" => null
            )), $decodedResponse);
    }

    public function testSaveActionRename()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/Test/1/save", array(
            "name" => "edited_test",
            "description" => "edited test description",
            "visibility" => Test::VISIBILITY_REGULAR,
            "code" => "code",
            "type" => Test::TYPE_CODE,
            "accessibility" => ATopEntity::ACCESS_PUBLIC
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(array(
            "result" => 0,
            "errors" => array(),
            "objectTimestamp" => $decodedResponse["objectTimestamp"],
            "object" => array(
                "class_name" => "Test",
                "id" => 1,
                "name" => "edited_test",
                "description" => "edited test description",
                "code" => "code",
                "visibility" => Test::VISIBILITY_REGULAR,
                'variables' => array(),
                'sourceWizard' => null,
                'sourceWizardName' => null,
                'sourceWizardTest' => null,
                'sourceWizardTestName' => null,
                "updatedOn" => $decodedResponse["object"]['updatedOn'],
                "updatedBy" => 'admin',
                "slug" => $decodedResponse["object"]['slug'],
                "type" => Test::TYPE_CODE,
                "accessibility" => ATopEntity::ACCESS_PUBLIC,
                "archived" => "0",
                "protected" => "0",
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
                "steps" => array(),
                "lockedBy" => null,
                "directLockBy" => null,
                "baseTemplate" => null
            )), $decodedResponse);
        $this->assertCount(1, self::$repository->findAll());
    }

    public function testSaveActionSameName()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/Test/1/save", array(
            "name" => "test",
            "description" => "edited test description",
            "type" => 0,
            "visibility" => Test::VISIBILITY_REGULAR,
            "code" => "code",
            "accessibility" => ATopEntity::ACCESS_PUBLIC));
        $fail_msg = "";
        if (!$client->getResponse()->isSuccessful()) {
            $crawler = $client->getCrawler();
            $fail_msg = $crawler->filter("title")->text();
        }
        $this->assertTrue($client->getResponse()->isSuccessful(), $fail_msg);
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(array(
            "result" => 0,
            "errors" => array(),
            "objectTimestamp" => $decodedResponse["objectTimestamp"],
            "object" => array(
                "class_name" => "Test",
                "id" => 1,
                "name" => "test",
                "description" => "edited test description",
                "code" => "code",
                "visibility" => Test::VISIBILITY_REGULAR,
                'variables' => array(),
                'sourceWizard' => null,
                'sourceWizardName' => null,
                'sourceWizardTest' => null,
                'sourceWizardTestName' => null,
                "updatedOn" => $decodedResponse["object"]['updatedOn'],
                "updatedBy" => 'admin',
                "slug" => $decodedResponse["object"]['slug'],
                "type" => Test::TYPE_CODE,
                "accessibility" => ATopEntity::ACCESS_PUBLIC,
                "archived" => "0",
                "protected" => "0",
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
                "steps" => array(),
                "lockedBy" => null,
                "directLockBy" => null,
                "baseTemplate" => null
            )), $decodedResponse);
        $this->assertCount(1, self::$repository->findAll());
    }

    public function testSaveActionNameAlreadyExists()
    {
        $client = self::createLoggedClient();

        $client->request("POST", "/admin/Test/-1/save", array(
            "name" => "new_test",
            "visibility" => Test::VISIBILITY_REGULAR,
            "type" => Test::TYPE_CODE,
            "accessibility" => ATopEntity::ACCESS_PUBLIC
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(array(
            "result" => 0,
            "errors" => array(),
            "objectTimestamp" => $decodedResponse["objectTimestamp"],
            "object" => array(
                "class_name" => "Test",
                "id" => 2,
                "name" => "new_test",
                "description" => "",
                "code" => "",
                "visibility" => Test::VISIBILITY_REGULAR,
                'variables' => array(),
                'sourceWizard' => null,
                'sourceWizardName' => null,
                'sourceWizardTest' => null,
                'sourceWizardTestName' => null,
                "updatedOn" => $decodedResponse["object"]['updatedOn'],
                "updatedBy" => 'admin',
                "slug" => $decodedResponse["object"]['slug'],
                "type" => Test::TYPE_CODE,
                "accessibility" => ATopEntity::ACCESS_PUBLIC,
                "archived" => "0",
                "protected" => "0",
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
                "steps" => array(),
                "lockedBy" => null,
                "directLockBy" => null,
                "baseTemplate" => null
            )
        ), $decodedResponse);
        $this->assertCount(2, self::$repository->findAll());

        $client->request("POST", "/admin/Test/1/save", array(
            "name" => "new_test",
            "description" => "edited test description",
            "visibility" => Test::VISIBILITY_REGULAR,
            "code" => "code"));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(array(
            "result" => 1,
            "object" => null,
            "objectTimestamp" => $decodedResponse["objectTimestamp"],
            "errors" => array("This name already exists in the system")
        ), $decodedResponse);
        $this->assertCount(2, self::$repository->findAll());
    }

    public function exportDataProvider()
    {
        return array(
            array(array(
                "Test" => array(
                    "id" => array(1),
                    "name" => array("test"),
                    "data" => array("0")
                )
            ), "yml"),
            array(array(
                "Test" => array(
                    "id" => array(1),
                    "name" => array("test"),
                    "data" => array("0")
                )
            ), "json"),
            array(array(
                "Test" => array(
                    "id" => array(1),
                    "name" => array("test"),
                    "data" => array("0")
                )
            ), "compressed")
        );
    }

}
