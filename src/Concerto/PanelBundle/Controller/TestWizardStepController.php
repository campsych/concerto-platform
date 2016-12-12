<?php

namespace Concerto\PanelBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Concerto\PanelBundle\Service\TestWizardService;
use Concerto\PanelBundle\Service\TestWizardStepService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Security("has_role('ROLE_WIZARD') or has_role('ROLE_SUPER_ADMIN')")
 */
class TestWizardStepController extends ASectionController {

    const ENTITY_NAME = "TestWizardStep";

    private $testWizardService;
    private $request;

    public function __construct(EngineInterface $templating, TestWizardStepService $service, Request $request, TranslatorInterface $translator, TestWizardService $testWizardService, TokenStorage $securityTokenStorage) {
        parent::__construct($templating, $service, $translator, $securityTokenStorage);

        $this->entityName = self::ENTITY_NAME;
        $this->testWizardService = $testWizardService;
        $this->request = $request;
    }

    public function collectionByWizardAction($wizard_id, $format) {
        return $this->templating->renderResponse('ConcertoPanelBundle::collection.' . $format . '.twig', array(
                    'collection' => $this->service->getByTestWizard($wizard_id)
        ));
    }

    public function saveAction($object_id) {
        $result = $this->service->save(//
                $this->securityTokenStorage->getToken()->getUser(), //
                $object_id, //
                $this->request->get("title"), //
                $this->request->get("description"), //
                $this->request->get("orderNum"), //
                $this->testWizardService->get($this->request->get("wizard")));
        return $this->getSaveResponse($result);
    }

    public function clearAction($wizard_id) {
        $this->service->clear($wizard_id);
        $response = new Response(json_encode(array("result" => 0)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

}
