<?php

namespace Concerto\PanelBundle\Controller;

use Concerto\PanelBundle\Service\TestSessionService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;

class TestSessionController {

    private $templating;
    private $service;
    private $request;
    private $translator;

    public function __construct(EngineInterface $templating, TestSessionService $service, Request $request, TranslatorInterface $translator) {
        $this->templating = $templating;
        $this->service = $service;
        $this->request = $request;
        $this->translator = $translator;
    }

    /**
     * Should only be called by other node.
     * 
     * @param string $test_slug
     * @param json serialized string $params
     * @param boolean $debug
     * @return Response
     */
    public function startNewAction($test_slug, $params = "{}", $debug = false) {
        $client_ip = $this->request->get("client_ip");
        $client_browser = $this->request->get("client_browser");
        $calling_node_ip = $this->request->getClientIp();
        $test_node_hash = $this->request->get("test_node_hash");
        
        $result = $this->service->startNewSession($test_node_hash, $test_slug, $params, $client_ip, $client_browser, $calling_node_ip, $debug);
        $response = new Response($result);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @param string $test_slug
     * @param json serialized string $params
     * @return Response
     */
    public function startDebugAction($test_slug, $params = "{}") {
        return $this->startNewAction($test_slug, $params, true);
    }

    /**
     * Should only be called by other node.
     * 
     * @param string $session_hash
     * @return Response
     */
    public function submitAction($session_hash) {
        $client_ip = $this->request->get("client_ip");
        $client_browser = $this->request->get("client_browser");
        $calling_node_ip = $this->request->getClientIp();
        $test_node_hash = $this->request->get("test_node_hash");
        return new Response($this->service->submit($test_node_hash, $session_hash, $this->request->get("values"), $client_ip, $client_browser, $calling_node_ip));
    }
    
    public function keepAliveAction($session_hash) {
        $client_ip = $this->request->get("client_ip");
        $calling_node_ip = $this->request->getClientIp();
        $test_node_hash = $this->request->get("test_node_hash");
        
        $result = $this->service->keepAlive($test_node_hash, $session_hash, $client_ip, $calling_node_ip);
        $response = new Response($result);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * Should only be called by other node.
     * 
     * @param string $session_hash
     * @return Response
     */
    public function resumeAction($session_hash) {
        $calling_node_ip = $this->request->getClientIp();
        $test_node_hash = $this->request->get("test_node_hash");
        
        $result = $this->service->resume($test_node_hash, $session_hash, $calling_node_ip);
        $response = new Response($result);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * Should only be called by other node.
     * 
     * @param string $session_hash
     * @return Response
     */
    public function resultsAction($session_hash) {
        $calling_node_ip = $this->request->getClientIp();
        $test_node_hash = $this->request->get("test_node_hash");
        
        $result = $this->service->results($test_node_hash, $session_hash, $calling_node_ip);
        $response = new Response($result);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

}
