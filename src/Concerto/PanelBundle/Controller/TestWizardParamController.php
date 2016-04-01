<?php

namespace Concerto\PanelBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Concerto\PanelBundle\Service\TestWizardService;
use Concerto\PanelBundle\Service\TestVariableService;
use Concerto\PanelBundle\Service\TestWizardParamService;
use Concerto\PanelBundle\Service\TestWizardStepService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Security("has_role('ROLE_WIZARD') or has_role('ROLE_SUPER_ADMIN')")
 */
class TestWizardParamController extends ASectionController {

    const ENTITY_NAME = "TestWizardParam";

    private $testWizardService;
    private $testWizardStepService;
    private $testVariableService;
    private $request;

    public function __construct(EngineInterface $templating, TestWizardParamService $service, Request $request, TranslatorInterface $translator, TestVariableService $testVariableServce, TestWizardStepService $testWizardStepService, TestWizardService $testWizardService, TokenStorage $securityTokenStorage) {
        parent::__construct($templating, $service, $translator, $securityTokenStorage);

        $this->entityName = self::ENTITY_NAME;
        $this->testWizardService = $testWizardService;
        $this->testWizardStepService = $testWizardStepService;
        $this->testVariableService = $testVariableServce;
        $this->request = $request;
    }

    public function collectionByWizardAction($wizard_id) {
        return $this->templating->renderResponse('ConcertoPanelBundle::collection.json.twig', array(
                    'collection' => $this->service->getByTestWizard($wizard_id)
        ));
    }

    public function collectionByWizardAndTypeAction($wizard_id, $type) {
        return $this->templating->renderResponse('ConcertoPanelBundle::collection.json.twig', array(
                    'collection' => $this->service->getByTestWizardAndType($wizard_id, $type)
        ));
    }

    public function saveAction($object_id) {
        $result = $this->service->save(//
                $this->securityTokenStorage->getToken()->getUser(), //
                $object_id, //
                $this->testVariableService->get($this->request->get("testVariable")), //
                $this->request->get("label"), //
                $this->request->get("type"), //
                $this->request->get("serializedDefinition"), //
                $this->request->get("hideCondition"), //
                $this->request->get("description"), //
                $this->request->get("passableThroughUrl"), //
                $this->request->get("value"), //
                $this->testWizardStepService->get($this->request->get("wizardStep")), //
                $this->request->get("order"), //
                $this->testWizardService->get($this->request->get("wizard")));
        return $this->getSaveResponse($result);
    }

    public function clearAction($wizard_id) {
        $this->service->clear($wizard_id);
        $response = new Response(json_encode(array("result" => 0, "object_id" => $wizard_id)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

}
