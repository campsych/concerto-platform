<?php

namespace Concerto\PanelBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Concerto\PanelBundle\Service\AdministrationService;
use Concerto\TestBundle\Service\TestSessionCountService;
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
    private $sessionCountService;

    public function __construct(EngineInterface $templating, AdministrationService $service, TestSessionCountService $sessionCountService, Request $request) {
        $this->templating = $templating;
        $this->service = $service;
        $this->sessionCountService = $sessionCountService;
        $this->request = $request;
    }

    public function settingsMapAction() {
        return $this->templating->renderResponse('ConcertoPanelBundle::collection.json.twig', array(
                    'collection' => $this->service->getSettingsMap()
        ));
    }

    public function messagesCollectionAction() {
        return $this->templating->renderResponse('ConcertoPanelBundle::collection.json.twig', array(
                    'collection' => $this->service->getMessagesCollection()
        ));
    }

    public function updateSettingsMapAction() {
        $this->service->setSettings(json_decode($this->request->get("map")));
        $result = array("result" => 0);
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function sessionCountCollectionAction($filter) {
        $collection = $this->sessionCountService->getCollection(json_decode($filter, true));
        return $this->templating->renderResponse('ConcertoPanelBundle::collection.json.twig', array(
                    'collection' => $collection
        ));
    }

    public function clearSessionCountAction() {
        $this->sessionCountService->clear();
        $result = array("result" => 0);
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function deleteMessageAction($object_ids) {
        $result = $this->service->deleteMessage($object_ids);
        $response = new Response(json_encode(array("result" => 0, "object_ids" => $object_ids)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function clearMessagesAction() {
        $this->service->clearMessages();
        $response = new Response(json_encode(array("result" => 0)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

}
