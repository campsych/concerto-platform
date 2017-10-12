<?php

namespace Concerto\PanelBundle\Tests\Controller\FunctionalTests;

use Concerto\PanelBundle\Tests\AFunctionalTest;

class FileBrowserControllerTest extends AFunctionalTest {

    public function testFileListAction() {
        $client = self::createLoggedClient();

        $client->request('GET', '/admin/file/list');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($client->getResponse()->headers->contains("Content-Type", 'application/json'));
        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey("result", $result);
        $this->assertEquals(0, $result["result"]);
        $this->assertArrayHasKey("files", $result);
        $this->assertCount(16, $result["files"]);

        $elems = array(
            array("name" => "csv_table.csv", "url" => "/bundles/concertopanel/files/csv_table.csv"),
            array("name" => "DataTable_1.concerto.json", "url" => "/bundles/concertopanel/files/DataTable_1.concerto.json"),
            array("name" => "Test_1.concerto.json", "url" => "/bundles/concertopanel/files/Test_1.concerto.json"),
            array("name" => "large_table.csv", "url" => "/bundles/concertopanel/files/large_table.csv"),
            array("name" => "ViewTemplate_8.concerto.json", "url" => "/bundles/concertopanel/files/ViewTemplate_8.concerto.json")
        );
        foreach($elems as $elem){
            $this->assertContains($elem, $result["files"]);
        }
    }

}
