<?php

namespace Tests\Concerto\PanelBundle\Controller\FunctionalTests;

use Tests\Concerto\PanelBundle\AFunctionalTest;

class FileBrowserControllerTest extends AFunctionalTest {

    public function testFileListAction() {
        $client = self::createLoggedClient();

        $client->request('GET', '/admin/file/list');

        //HTTP response
        $fail_msg = "";
        if (!$client->getResponse()->isSuccessful()) {
            $crawler = $client->getCrawler();
            $fail_msg = $crawler->filter("title")->text();
        }
        $this->assertTrue($client->getResponse()->isSuccessful(), $fail_msg);
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey("result", $result);
        $this->assertEquals(0, $result["result"]);
        $this->assertArrayHasKey("files", $result);
        $this->assertCount(0, $result["files"]);
    }

}
