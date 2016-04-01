<?php

namespace Concerto\PanelBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Concerto\PanelBundle\Service\PanelService;
use Concerto\PanelBundle\Service\FileService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PanelController {

    private $templating;
    private $service;
    private $request;
    private $fileService;

    public function __construct(EngineInterface $templating, PanelService $service, FileService $fileService, Request $request) {
        $this->templating = $templating;
        $this->service = $service;
        $this->fileService = $fileService;
        $this->request = $request;
    }

    /**
     * @return Response
     */
    public function indexAction() {
        return $this->templating->renderResponse('ConcertoPanelBundle:Panel:index.html.twig');
    }

    /**
     * @return Response
     */
    public function breadcrumbsAction() {
        return $this->templating->renderResponse('ConcertoPanelBundle::breadcrumbs.html.twig');
    }

    /**
     * @return Response
     */
    public function loginAction() {
        return $this->templating->renderResponse('ConcertoPanelBundle:Panel:login.html.twig', array(
                    'last_username' => $this->request->getSession()->get(Security::LAST_USERNAME),
                    'error' => $this->service->getLoginErrors($this->request->get(Security::AUTHENTICATION_ERROR), $this->request->getSession())));
    }

    /**
     * @return Response
     */
    public function changeLocaleAction($locale) {
        $this->request->setLocale($locale);
        $this->request->setDefaultLocale($locale);
        $this->service->setLocale($this->request->getSession(), $locale);

        return new RedirectResponse($this->request->getUriForPath("/admin"));
    }
}
