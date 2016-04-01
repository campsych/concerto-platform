<?php

namespace Concerto\PanelBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Concerto\PanelBundle\Service\TestService;
use Concerto\PanelBundle\Service\TestNodeService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Security("has_role('ROLE_TEST') or has_role('ROLE_SUPER_ADMIN')")
 */
class TestNodeController extends ASectionController {

    const ENTITY_NAME = "TestNode";

    private $request;
    private $testService;

    public function __construct(EngineInterface $templating, TestNodeService $nodeService, TestService $testService, Request $request, TranslatorInterface $translator, TokenStorage $securityTokenStorage) {
        parent::__construct($templating, $nodeService, $translator, $securityTokenStorage);

        $this->entityName = self::ENTITY_NAME;

        $this->testService = $testService;
        $this->request = $request;
    }

    public function collectionByFlowTestAction($test_id) {
        return $this->templating->renderResponse('ConcertoPanelBundle::collection.json.twig', array(
                    'collection' => $this->service->getByFlowTest($test_id)
        ));
    }

    public function saveAction($object_id) {
        $result = $this->service->save(//
                $this->securityTokenStorage->getToken()->getUser(), //
                $object_id, //
                $this->request->get("type"), //
                $this->request->get("posX"), //
                $this->request->get("posY"), //
                $this->testService->get($this->request->get("flowTest")), //
                $this->testService->get($this->request->get("sourceTest")));
        return $this->getSaveResponse($result);
    }

}
