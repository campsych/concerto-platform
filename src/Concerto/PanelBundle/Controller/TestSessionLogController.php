<?php

namespace Concerto\PanelBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Concerto\PanelBundle\Service\TestSessionLogService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Security("has_role('ROLE_TEST') or has_role('ROLE_SUPER_ADMIN')")
 */
class TestSessionLogController extends ASectionController {
    
    const ENTITY_NAME = "TestSessionLog";

    private $request;
    
    public function __construct(EngineInterface $templating, TestSessionLogService $service, Request $request, TranslatorInterface $translator, TokenStorage $securityTokenStorage) {
        parent::__construct($templating, $service, $translator, $securityTokenStorage);
        
        $this->entityName = self::ENTITY_NAME;
        $this->request = $request;
    }

    public function collectionByTestAction($test_id) {
        return $this->templating->renderResponse('ConcertoPanelBundle::collection.json.twig', array(
            'collection' => $this->service->getByTest($test_id)
            )
        );
    }

    public function clearAction($test_id) {
        $this->service->clear($test_id);
        $response = new Response(json_encode(array("result" => 0)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}