<?php

namespace Concerto\PanelBundle\Controller;

use Concerto\PanelBundle\Service\TestService;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Concerto\PanelBundle\Service\TestWizardService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Concerto\PanelBundle\Service\ImportService;
use Concerto\PanelBundle\Service\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Security("has_role('ROLE_WIZARD') or has_role('ROLE_SUPER_ADMIN')")
 */
class TestWizardController extends AExportableTabController {

    const ENTITY_NAME = "TestWizard";
    const EXPORT_FILE_PREFIX = "TestWizard_";

    private $testService;
    private $userService;

    public function __construct($environment, EngineInterface $templating, TestWizardService $service, Request $request, TranslatorInterface $translator, TokenStorage $securityTokenStorage, TestService $testService, ImportService $importService, UserService $userService) {
        parent::__construct($environment, $templating, $service, $request, $translator, $securityTokenStorage, $importService);

        $this->entityName = self::ENTITY_NAME;
        $this->exportFilePrefix = self::EXPORT_FILE_PREFIX;
        
        $this->testService = $testService;
        $this->userService = $userService;
    }

    public function saveAction($object_id) {
        $result = $this->service->save(
                $this->securityTokenStorage->getToken()->getUser(), //
                $object_id, //
                $this->request->get("name"), //
                $this->request->get("description"), //
                $this->request->get("accessibility"), //
                $this->request->get("protected") === "1", //
                $this->request->get("archived") === "1", //
                $this->userService->get($this->request->get("owner")), //
                $this->request->get("groups"), //
                $this->testService->get($this->request->get("test")), //
                $this->request->get("serializedSteps") //
        );
        return $this->getSaveResponse($result);
    }

}
