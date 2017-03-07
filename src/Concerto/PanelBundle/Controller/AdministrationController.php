<?php

namespace Concerto\PanelBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Concerto\PanelBundle\Service\AdministrationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Security("has_role('ROLE_SUPER_ADMIN')")
 */
class AdministrationController {

    private $templating;
    private $request;
    private $service;

    public function __construct(EngineInterface $templating, AdministrationService $service, Request $request) {
        $this->templating = $templating;
        $this->service = $service;
        $this->request = $request;
    }

    public function settingsMapAction() {
        return $this->templating->renderResponse('ConcertoPanelBundle::collection.json.twig', array(
                    'collection' => $this->service->getSettingsMap()
        ));
    }

    public function updateSettingsMapAction() {
        $this->service->setSettings(json_decode($this->request->get("map")));
        $result = array("result" => 0);
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

}
