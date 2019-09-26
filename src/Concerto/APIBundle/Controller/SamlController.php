<?php

namespace Concerto\APIBundle\Controller;

use Concerto\APIBundle\Service\SamlService;
use Concerto\PanelBundle\Service\AdministrationService;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/saml")
 */
class SamlController
{
    private $service;
    private $administrationService;

    public function __construct(AdministrationService $administrationService, SamlService $service)
    {
        $this->administrationService = $administrationService;
        $this->service = $service;
    }

    /**
     * @Route("/login", methods={"GET"})
     * @param Request $request
     * @return Response
     */
    public function loginAction(Request $request)
    {
        if (!$this->administrationService->isApiEnabled())
            return new Response("API disabled", Response::HTTP_FORBIDDEN);

        $redirectTo = $request->get("redirectTo");
        $this->service->login($redirectTo);

        return new Response('', 200);
    }

    /**
     * @Route("/logout", methods={"GET"})
     * @param Request $request
     * @return Response
     */
    public function logoutAction(Request $request)
    {
        if (!$this->administrationService->isApiEnabled())
            return new Response("API disabled", Response::HTTP_FORBIDDEN);

        $redirectTo = $request->get("redirectTo");
        $this->service->logout($redirectTo);

        return new Response('', 200);
    }

    /**
     * @Route("/acs", methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function acsAction(Request $request)
    {
        if (!$this->administrationService->isApiEnabled())
            return new Response("API disabled", Response::HTTP_FORBIDDEN);

        $stateRelay = $request->get("RelayState");
        $token = $this->service->acs($stateRelay);

        if ($token !== false) {
            if ($stateRelay !== null) {
                $response = new RedirectResponse($stateRelay);
            } else {
                $response = new Response("", 200);
            }
            $response->headers->setCookie(new Cookie("concertoSamlTokenHash", $token));
        } else {
            $response = new Response("", 403);
        }
        return $response;
    }

    /**
     * @Route("/sls", methods={"GET"})
     * @param Request $request
     * @return Response
     */
    public function slsAction(Request $request)
    {
        if (!$this->administrationService->isApiEnabled())
            return new Response("API disabled", Response::HTTP_FORBIDDEN);

        $tokenHash = $request->cookies->get("concertoSamlTokenHash");
        $stateRelay = $request->get("RelayState");
        $success = $this->service->sls($tokenHash, $stateRelay);

        if ($success) {
            if ($stateRelay !== null) {
                $response = new RedirectResponse($stateRelay);
            } else {
                $response = new Response("", 200);
            }
        } else {
            $response = new Response("", 403);
        }

        return $response;
    }

    /**
     * @Route("/metadata", methods={"GET"})
     * @return Response
     */
    public function metadataAction()
    {
        if (!$this->administrationService->isApiEnabled())
            return new Response("API disabled", Response::HTTP_FORBIDDEN);

        $metadata = $this->service->metadata();
        if ($metadata === false) {
            return new Response('', 500);
        }

        $response = new Response($metadata, 200);
        $response->headers->set('Content-Type', 'text/xml');
        return $response;
    }
}