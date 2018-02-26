<?php

namespace Concerto\PanelBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Concerto\PanelBundle\Service\TestWizardService;
use Concerto\PanelBundle\Service\TestVariableService;
use Concerto\PanelBundle\Service\TestWizardParamService;
use Concerto\PanelBundle\Service\TestWizardStepService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Route("/admin")
 * @Security("has_role('ROLE_WIZARD') or has_role('ROLE_SUPER_ADMIN')")
 */
class TestWizardParamController extends ASectionController
{

    const ENTITY_NAME = "TestWizardParam";

    private $testWizardService;
    private $testWizardStepService;
    private $testVariableService;

    public function __construct(EngineInterface $templating, TestWizardParamService $service, TranslatorInterface $translator, TestVariableService $testVariableServce, TestWizardStepService $testWizardStepService, TestWizardService $testWizardService, TokenStorageInterface $securityTokenStorage)
    {
        parent::__construct($templating, $service, $translator, $securityTokenStorage);

        $this->entityName = self::ENTITY_NAME;
        $this->testWizardService = $testWizardService;
        $this->testWizardStepService = $testWizardStepService;
        $this->testVariableService = $testVariableServce;
    }

    /**
     * @Route("/TestWizardParam/fetch/{object_id}/{format}", name="TestWizardParam_object", defaults={"format":"json"})
     * @param $object_id
     * @param string $format
     * @return Response
     */
    public function objectAction($object_id, $format = "json")
    {
        return parent::objectAction($object_id, $format);
    }

    /**
     * @Route("/TestWizardParam/collection/{format}", name="TestWizardParam_collection", defaults={"format":"json"})
     * @param string $format
     * @return Response
     */
    public function collectionAction($format = "json")
    {
        return parent::collectionAction($format);
    }

    /**
     * @Route("/TestWizardParam/TestWizard/{wizard_id}/collection", name="TestWizardParam_collection_by_wizard")
     * @param $wizard_id
     * @return Response
     */
    public function collectionByWizardAction($wizard_id)
    {
        return $this->templating->renderResponse('ConcertoPanelBundle::collection.json.twig', array(
            'collection' => $this->service->getByTestWizard($wizard_id)
        ));
    }

    /**
     * @Route("/TestWizardParam/TestWizard/{wizard_id}/type/{type}/collection", name="TestWizardParam_collection_by_wizard_and_type")
     * @param $wizard_id
     * @param $type
     * @return Response
     */
    public function collectionByWizardAndTypeAction($wizard_id, $type)
    {
        return $this->templating->renderResponse('ConcertoPanelBundle::collection.json.twig', array(
            'collection' => $this->service->getByTestWizardAndType($wizard_id, $type)
        ));
    }

    /**
     * @Route("/TestWizardParam/{object_ids}/delete", name="TestWizardParam_delete")
     * @Method(methods={"POST"})
     * @param string $object_ids
     * @return Response
     */
    public function deleteAction($object_ids)
    {
        return parent::deleteAction($object_ids);
    }

    /**
     * @Route("/TestWizardParam/{object_id}/save", name="TestWizardParam_save")
     * @Method(methods={"POST"})
     * @param Request $request
     * @param $object_id
     * @return Response
     */
    public function saveAction(Request $request, $object_id)
    {
        $result = $this->service->save(
            $this->securityTokenStorage->getToken()->getUser(),
            $object_id,
            $this->testVariableService->get($request->get("testVariable")),
            $request->get("label"),
            $request->get("type"),
            $request->get("serializedDefinition"),
            $request->get("hideCondition"),
            $request->get("description"),
            $request->get("passableThroughUrl"),
            $request->get("value"),
            $this->testWizardStepService->get($request->get("wizardStep")),
            $request->get("order"),
            $this->testWizardService->get($request->get("wizard")));
        return $this->getSaveResponse($result);
    }

    /**
     * @Route("/TestWizardParam/TestWizard/{wizard_id}/clear", name="TestWizardParam_clear")
     * @Method(methods={"POST"})
     * @param $wizard_id
     * @return Response
     */
    public function clearAction($wizard_id)
    {
        $this->service->clear($wizard_id);
        $response = new Response(json_encode(array("result" => 0)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

}
