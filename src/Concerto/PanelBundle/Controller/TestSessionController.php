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
}
