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
class TestNodeConnectionController extends ASectionController
{

    const ENTITY_NAME = "TestNodeConnection";

    private $testService;
    private $testNodeService;
    private $testPortService;

    public function __construct(EngineInterface $templating, TestService $testService, TestNodeConnectionService $connectionService, TestNodeService $nodeService, TestNodePortService $portService, TranslatorInterface $translator, TokenStorage $securityTokenStorage)
    {
        parent::__construct($templating, $connectionService, $translator, $securityTokenStorage);

        $this->entityName = self::ENTITY_NAME;

        $this->testService = $testService;
        $this->testNodeService = $nodeService;
        $this->testPortService = $portService;
    }

    public function collectionByFlowTestAction($test_id)
    {
        return $this->templating->renderResponse('ConcertoPanelBundle::collection.json.twig', array(
            'collection' => $this->service->getByFlowTest($test_id)
        ));
    }

    public function saveAction(Request $request, $object_id)
    {
        $sourcePort = $request->get("sourcePort");
        $destinationPort = $request->get("destinationPort");

        $result = $this->service->save(
            $this->securityTokenStorage->getToken()->getUser(),
            $object_id,
            $this->testService->get($request->get("flowTest")),
            $this->testNodeService->get($request->get("sourceNode")),
            $sourcePort ? $this->testPortService->get($sourcePort) : null,
            $this->testNodeService->get($request->get("destinationNode")),
            $destinationPort ? $this->testPortService->get($destinationPort) : null,
            $request->get("returnFunction"),
            false,
            $request->get("default") === "1");
        return $this->getSaveResponse($result);
    }

}
