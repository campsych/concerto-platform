<?php

namespace Concerto\PanelBundle\Controller;

use Concerto\PanelBundle\Service\AdministrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    private $templating;
    private $administrationService;

    public function __construct(EngineInterface $templating, AdministrationService $administrationService)
    {
        $this->templating = $templating;
        $this->administrationService = $administrationService;
    }

    /**
     * @Route("/", name="home")
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $test = $this->administrationService->getHomeTest();
        if ($test === null) return new Response("", Response::HTTP_NOT_FOUND);

        $params = [];
        $keys = $request->query->keys();
        foreach ($keys as $k) {
            $params[$k] = $request->query->get($k);
        }

        return $this->forward("Concerto\TestBundle\Controller\TestRunnerController::startTestAction", [
            "test_slug" => $test->getSlug()
        ], $params);
    }

}
