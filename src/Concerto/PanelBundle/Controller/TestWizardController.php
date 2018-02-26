<?php

namespace Concerto\PanelBundle\Controller;

use Concerto\PanelBundle\Service\FileService;
use Concerto\PanelBundle\Service\TestService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Concerto\PanelBundle\Service\TestWizardService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Concerto\PanelBundle\Service\ImportService;
use Concerto\PanelBundle\Service\ExportService;
use Concerto\PanelBundle\Service\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/admin")
 * @Security("has_role('ROLE_WIZARD') or has_role('ROLE_SUPER_ADMIN')")
 */
class TestWizardController extends AExportableTabController
{

    const ENTITY_NAME = "TestWizard";
    const EXPORT_FILE_PREFIX = "TestWizard_";

    private $testService;
    private $userService;

    public function __construct($environment, EngineInterface $templating, TestWizardService $service, TranslatorInterface $translator, TokenStorageInterface $securityTokenStorage, TestService $testService, ImportService $importService, ExportService $exportService, UserService $userService, FileService $fileService)
    {
        parent::__construct($environment, $templating, $service, $translator, $securityTokenStorage, $importService, $exportService, $fileService);

        $this->entityName = self::ENTITY_NAME;
        $this->exportFilePrefix = self::EXPORT_FILE_PREFIX;

        $this->testService = $testService;
        $this->userService = $userService;
    }

    /**
     * @Route("/TestWizard/fetch/{object_id}/{format}", name="TestWizard_object", defaults={"format":"json"})
     * @param $object_id
     * @param string $format
     * @return Response
     */
    public function objectAction($object_id, $format = "json")
    {
        return parent::objectAction($object_id, $format);
    }

    /**
     * @Route("/TestWizard/collection/{format}", name="TestWizard_collection", defaults={"format":"json"})
     * @param string $format
     * @return Response
     */
    public function collectionAction($format = "json")
    {
        return parent::collectionAction($format);
    }

    /**
     * @Route("/TestWizard/form/{action}", name="TestWizard_form", defaults={"action":"edit"})
     * @param string $action
     * @param array $params
     * @return Response
     */
    public function formAction($action = "edit", $params = array())
    {
        return parent::formAction($action, $params);
    }

    /**
     * @Route("/TestWizard/{object_id}/save", name="TestWizard_save")
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
            $request->get("name"),
            $request->get("description"),
            $request->get("accessibility"),
            $request->get("archived") === "1",
            $this->userService->get($request->get("owner")),
            $request->get("groups"),
            $this->testService->get($request->get("test")),
            $request->get("serializedSteps")
        );
        return $this->getSaveResponse($result);
    }

    /**
     * @Route("/TestWizard/{object_id}/copy", name="TestWizard_copy")
     * @Method(methods={"POST"})
     * @param Request $request
     * @param $object_id
     * @return Response
     */
    public function copyAction(Request $request, $object_id)
    {
        return parent::copyAction($request, $object_id);
    }

    /**
     * @Route("/TestWizard/{object_ids}/delete", name="TestWizard_delete")
     * @Method(methods={"POST"})
     * @param string $object_ids
     * @return Response
     */
    public function deleteAction($object_ids)
    {
        return parent::deleteAction($object_ids);
    }

    /**
     * @Route("/TestWizard/{object_ids}/export/{format}", name="TestWizard_export", defaults={"format":"compressed"})
     * @param $object_ids
     * @param string $format
     * @return Response
     */
    public function exportAction($object_ids, $format = ExportService::FORMAT_COMPRESSED)
    {
        return parent::exportAction($object_ids, $format);
    }

    /**
     * @Route("/TestWizard/import", name="TestWizard_import")
     * @Method(methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function importAction(Request $request)
    {
        return parent::importAction($request);
    }

    /**
     * @Route("/TestWizard/import/status", name="TestWizard_pre_import_status")
     * @param Request $request
     * @return Response
     */
    public function preImportStatusAction(Request $request)
    {
        return parent::preImportStatusAction($request);
    }
}
