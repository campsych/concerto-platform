<?php

namespace Concerto\PanelBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Concerto\PanelBundle\Service\HomeService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController
{

    private $templating;
    private $homeService;

    public function __construct(EngineInterface $templating, HomeService $homeService)
    {
        $this->templating = $templating;
        $this->homeService = $homeService;
    }

    /**
     * @Route("/", name="home")
     * @return Response
     */
    public function indexAction()
    {
        return $this->templating->renderResponse('ConcertoPanelBundle::home.html.twig');
    }

    /**
     * @Route("/featured/collection/{format}", name="home_featured_collection", defaults={"format":"json"})
     * @param string $format
     * @return Response
     */
    public function featuredCollectionAction($format = "json")
    {
        return $this->templating->renderResponse("ConcertoPanelBundle::collection.$format.twig", array(
            'collection' => $this->homeService->getFeaturedTests()
        ));
    }

}
