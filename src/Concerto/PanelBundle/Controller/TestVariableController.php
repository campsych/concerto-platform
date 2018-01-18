<?php

namespace Concerto\PanelBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Concerto\PanelBundle\Service\TestVariableService;
use Symfony\Component\HttpFoundation\Request;
use Concerto\PanelBundle\Service\TestService;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Security("has_role('ROLE_TEST') or has_role('ROLE_SUPER_ADMIN')")
 */
class TestVariableController extends ASectionController
{

    const ENTITY_NAME = "TestVariable";

    private $testService;

    public function __construct(EngineInterface $templating, TestVariableService $service, TranslatorInterface $translator, TestService $testService, TokenStorage $securityTokenStorage)
    {
        parent::__construct($templating, $service, $translator, $securityTokenStorage);

        $this->entityName = self::ENTITY_NAME;
        $this->testService = $testService;
    }

    public function collectionByTestAction($test_id)
    {
        $collection = $this->service->getAllVariables($test_id);
        return $this->templating->renderResponse("ConcertoPanelBundle::collection.json.twig", array("collection" => $collection));
    }

    public function parametersCollectionAction($test_id)
    {
        $collection = $this->service->getParameters($test_id);
        return $this->templating->renderResponse("ConcertoPanelBundle::collection.json.twig", array("collection" => $collection));
    }

    public function returnsCollectionAction($test_id)
    {
        $collection = $this->service->getReturns($test_id);
        return $this->templating->renderResponse("ConcertoPanelBundle::collection.json.twig", array("collection" => $collection));
    }

    public function branchesCollectionAction($test_id)
    {
        $collection = $this->service->getBranches($test_id);
        return $this->templating->renderResponse("ConcertoPanelBundle::collection.json.twig", array("collection" => $collection));
    }

    public function saveAction(Request $request, $object_id)
    {
        $result = $this->service->save(
            $this->securityTokenStorage->getToken()->getUser(),
            $object_id,
            $request->get("name"),
            $request->get("type"),
            $request->get("description"),
            $request->get("passableThroughUrl"),
            $request->get("value"),
            $this->testService->get($request->get("test"))
        );
        return $this->getSaveResponse($result);
    }

}
