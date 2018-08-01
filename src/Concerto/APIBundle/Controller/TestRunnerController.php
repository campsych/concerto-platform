<?php

namespace Concerto\APIBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Concerto\TestBundle\Service\TestRunnerService;
use Concerto\PanelBundle\Service\AdministrationService;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @Route("/api/runner")
 */
class TestRunnerController
{

    private $service;
    private $administrationService;
    private $session;

    public function __construct(TestRunnerService $service, AdministrationService $administrationService, SessionInterface $session)
    {
        $this->service = $service;
        $this->administrationService = $administrationService;
        $this->session = $session;
    }

    /**
     * @Route("/test/{test_slug}/session/start/{params}", defaults={"test_name":null,"params":"{}","debug":false})
     * @Route("/test_n/{test_name}/session/start/{params}", defaults={"test_slug":null,"params":"{}","debug":false})
     * @Method({"POST"})
     * @param Request $request
     * @param $test_slug
     * @param $test_name
     * @param string $params
     * @param bool $debug
     * @return Response
     */
    public function startNewSessionAction(Request $request, $test_slug, $test_name = null, $params = "{}", $debug = false)
    {
        if (!$this->administrationService->isApiEnabled())
            return new Response("API disabled", Response::HTTP_FORBIDDEN);

        $params = json_decode($params, true);
        $content = json_decode($request->getContent(), true);
        foreach ($content as $k => $v) {
            $params[$k] = $content[$k];
        }
        $params = json_encode($params);

        $result = $this->service->startNewSession(
            $test_slug,
            $test_name,
            $params,
            $request->getClientIp(),
            $request->server->get('HTTP_USER_AGENT'),
            $debug
        );
        $response = new Response($result, $this->getHttpCode($result));
        $response->headers->set('Content-Type', 'application/json');

        $this->session->set("templateStartTime", microtime(true));
        return $response;
    }

    /**
     * @Route("/test/session/{session_hash}/submit")
     * @Method(methods={"POST"})
     * @param Request $request
     * @param string $session_hash
     * @return Response
     */
    public function submitToSessionAction(Request $request, $session_hash)
    {
        $time = microtime(true);
        $content = json_decode($request->getContent(), true);
        $values = array();
        if (array_key_exists("values", $content)) $values = $content["values"];

        $result = $this->service->submitToSession(
            $session_hash,
            $values,
            $request->getClientIp(),
            $request->server->get('HTTP_USER_AGENT'),
            $time
        );
        $response = new Response($result, $this->getHttpCode($result));
        $response->headers->set('Content-Type', 'application/json');

        $this->session->set("templateStartTime", microtime(true));
        return $response;
    }

    /**
     * @Route("/test/session/{session_hash}/worker")
     * @Method(methods={"POST"})
     * @param Request $request
     * @param string $session_hash
     * @return Response
     */
    public function backgroundWorkerAction(Request $request, $session_hash)
    {
        $time = microtime(true);
        $content = json_decode($request->getContent(), true);
        $values = array();
        if (array_key_exists("values", $content)) $values = $content["values"];

        $result = $this->service->backgroundWorker(
            $session_hash,
            $values,
            $request->getClientIp(),
            $request->server->get('HTTP_USER_AGENT'),
            $time
        );
        $response = new Response($result, $this->getHttpCode($result));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/test/session/{session_hash}/kill")
     * @Method(methods={"POST"})
     * @param Request $request
     * @param $session_hash
     * @return Response
     */
    public function killSessionAction(Request $request, $session_hash)
    {
        $result = $this->service->killSession(
            $session_hash,
            $request->getClientIp(),
            $request->server->get('HTTP_USER_AGENT')
        );
        $response = new Response($result, $this->getHttpCode($result));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/test/session/{session_hash}/keepalive")
     * @Method(methods={"POST"})
     * @param Request $request
     * @param string $session_hash
     * @return Response
     */
    public function keepAliveSessionAction(Request $request, $session_hash)
    {
        $result = $this->service->keepAliveSession(
            $session_hash,
            $request->getClientIp(),
            $request->server->get('HTTP_USER_AGENT')
        );
        $response = new Response($result, $this->getHttpCode($result));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/test/session/{session_hash}/upload")
     * @Method(methods={"POST","OPTIONS"})
     * @param Request $request
     * @param string $session_hash
     * @return Response
     */
    public function uploadFileAction(Request $request, $session_hash)
    {
        $result = $this->service->uploadFile(
            $session_hash,
            $request->files,
            $request->get("name")
        );
        $response = new Response($result, $this->getHttpCode($result));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    private function getHttpCode($result)
    {
        $decodedResult = json_decode($result, true);
        switch ($decodedResult["code"]) {
            case 8:
                return Response::HTTP_FORBIDDEN;
            case 12:
                return Response::HTTP_SERVICE_UNAVAILABLE;
            case 13:
            case 14:
                return Response::HTTP_NOT_FOUND;
            case -1:
                return Response::HTTP_INTERNAL_SERVER_ERROR;
        }
        return Response::HTTP_OK;
    }
}
