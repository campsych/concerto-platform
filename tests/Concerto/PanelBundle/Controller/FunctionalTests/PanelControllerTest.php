<?php

namespace Tests\Concerto\PanelBundle\Controller\FunctionalTests;

use Tests\Concerto\PanelBundle\AFunctionalTest;

class PanelControllerTest extends AFunctionalTest {

    public function testIndexAction() {
        $client = static::createClient();

        $client->request('GET', '/admin');
        //redirects to login page
        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertSame("/login", $client->getRequest()->getPathInfo());
    }
    
    public function testBreadcrumbsAction() {
        $client = self::createLoggedClient();

        $client->request('GET', '/admin/breadcrumbs');
        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    public function testLoginAction() {
        $client = static::createClient();

        $crawler = $client->request('GET', '/login');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($crawler->filter('input[name="_password"]')->count() > 0);
    }

    public function testChangeLocaleAction() {
        $client = self::createLoggedClient();

        $crawler = $client->request('GET', '/admin/locale/pl_PL' );
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertTrue($crawler->filter('html:contains("Zalogowany użytkownik")')->count() > 0);
    }

}
