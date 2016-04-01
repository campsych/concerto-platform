<?php

namespace Concerto\PanelBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Concerto\PanelBundle\Service\TestService;
use Concerto\PanelBundle\Service\TestNodeConnectionService;
use Concerto\PanelBundle\Service\TestNodeService;
use Concerto\PanelBundle\Service\TestNodePortService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Security("has_role('ROLE_TEST') or has_role('ROLE_SUPER_ADMIN')")
 */
class TestNodeConnectionController extends ASectionController {

    const ENTITY_NAME = "TestNodeConnection";

    private $request;
    private $testService;
    private $testNodeService;
    private $testPortService;

    public function __construct(EngineInterface $templating, TestService $testService, TestNodeConnectionService $connectionService, TestNodeService $nodeService, TestNodePortService $portService, Request $request, TranslatorInterface $translator, TokenStorage $securityTokenStorage) {
        parent::__construct($templating, $connectionService, $translator, $securityTokenStorage);

        $this->entityName = self::ENTITY_NAME;

        $this->testService = $testService;
        $this->testNodeService = $nodeService;
        $this->testPortService = $portService;
        $this->request = $request;
    }

    public function collectionByFlowTestAction($test_id) {
        return $this->templating->renderResponse('ConcertoPanelBundle::collection.json.twig', array(
                    'collection' => $this->service->getByFlowTest($test_id)
        ));
    }

    public function saveAction($object_id) {
        $sourcePort = $this->request->get("sourcePort");
        $destinationPort = $this->request->get("destinationPort");

        $result = $this->service->save(//
                $this->securityTokenStorage->getToken()->getUser(), //
                $object_id, //
                $this->testService->get($this->request->get("flowTest")), //
                $this->testNodeService->get($this->request->get("sourceNode")), //
                $sourcePort ? $this->testPortService->get($sourcePort) : null, //
                $this->testNodeService->get($this->request->get("destinationNode")), //
                $destinationPort ? $this->testPortService->get($destinationPort) : null, //
                $this->request->get("returnFunction"), //
                false);
        return $this->getSaveResponse($result);
    }

}
