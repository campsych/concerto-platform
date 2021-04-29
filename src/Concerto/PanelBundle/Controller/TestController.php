<?php

namespace Concerto\PanelBundle\Controller;

use Concerto\PanelBundle\Entity\Test;
use Concerto\PanelBundle\Service\FileService;
use Concerto\PanelBundle\Service\TestService;
use Concerto\PanelBundle\Service\ViewTemplateService;
use Symfony\Component\HttpFoundation\Response;
use Concerto\PanelBundle\Service\TestWizardService;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;
use Concerto\PanelBundle\Service\ImportService;
use Concerto\PanelBundle\Service\ExportService;
use Concerto\PanelBundle\Service\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Route("/admin")
 * @Security("has_role('ROLE_TEST') or has_role('ROLE_SUPER_ADMIN')")
 */
class TestController extends AExportableTabController
{

    const ENTITY_NAME = "Test";
    const EXPORT_FILE_PREFIX = "Test";

    private $testWizardService;
    private $userService;
    private $viewTemplateService;

    public function __construct($environment, EngineInterface $templating, TestService $service, TranslatorInterface $translator, TestWizardService $testWizardService, ImportService $importService, ExportService $exportService, UserService $userService, FileService $fileService, ViewTemplateService $viewTemplateService)
    {
        parent::__construct($environment, $templating, $service, $translator, $importService, $exportService, $fileService);

        $this->entityName = self::ENTITY_NAME;
        $this->exportFilePrefix = self::EXPORT_FILE_PREFIX;

        $this->testWizardService = $testWizardService;
        $this->userService = $userService;
        $this->viewTemplateService = $viewTemplateService;
    }

    /**
     * @Route("/Test/fetch/{object_id}/{format}", name="Test_object", defaults={"format":"json"})
     * @param $object_id
     * @param string $format
     * @return Response
     */
    public function objectAction($object_id, $format = "json")
    {
        return parent::objectAction($object_id, $format);
    }

    /**
     * @Route("/Test/collection/{format}", name="Test_collection", defaults={"format":"json"})
     * @param string $format
     * @return Response
     */
    public function collectionAction($format = "json")
    {
        $collection = $this->service->getAll();
        foreach ($collection as $test) {
            /**
             * @var Test $test
             */
            $test->clearNodes();
            $test->clearNodesConnections();
        }
        return $this->templating->renderResponse("ConcertoPanelBundle::collection.$format.twig", array(
            'collection' => $collection
        ));
    }

    /**
     * @Route("/Test/{object_id}/toggleLock", name="Test_toggleLock")
     * @param Request $request
     * @param $object_id
     * @return Response
     */
    public function toggleLock(Request $request, $object_id)
    {
        return parent::toggleLock($request, $object_id);
    }

    /**
     * @Route("/Test/form/{action}", name="Test_form", defaults={"action":"edit"})
     * @param string $action
     * @param array $params
     * @return Response
     */
    public function formAction($action = "edit", $params = array())
    {
        return parent::formAction($action, $params);
    }

    /**
     * @Route("/Test/{object_id}/save", name="Test_save", methods={"POST"})
     * @param Request $request
     * @param $object_id
     * @return Response
     */
    public function saveAction(Request $request, $object_id)
    {
        if (!$this->service->canBeModified($object_id, $request->get("updatedOn"), $errorMessages)) {
            $response = new Response(json_encode(array("result" => 1, "errors" => $this->trans($errorMessages))));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $result = $this->service->save(
            $object_id,
            $request->get("name"),
            $request->get("description"),
            $request->get("accessibility"),
            $request->get("archived") === "1",
            $this->userService->get($request->get("owner")),
            $request->get("groups"),
            $request->get("visibility"),
            $request->get("type"),
            $request->get("code"),
            $this->testWizardService->get($request->get("sourceWizard"), false),
            $request->get("slug"),
            $request->get("serializedVariables"),
            $this->viewTemplateService->get($request->get("baseTemplate"), false),
            $request->get("protected") === "1"
        );
        return $this->getSaveResponse($result);
    }

    /**
     * @Route("/Test/{object_id}/copy", name="Test_copy", methods={"POST"})
     * @param Request $request
     * @param $object_id
     * @return Response
     */
    public function copyAction(Request $request, $object_id)
    {
        return parent::copyAction($request, $object_id);
    }

    /**
     * @Route("/Test/{object_ids}/delete", name="Test_delete", methods={"POST"})
     * @param Request $request
     * @param $object_ids
     * @return Response
     */
    public function deleteAction(Request $request, $object_ids)
    {
        return parent::deleteAction($request, $object_ids);
    }

    /**
     * @Route("/Test/{instructions}/export/{format}", name="Test_export", defaults={"format":"yml"})
     * @param string $instructions
     * @param string $format
     * @return Response
     */
    public function exportAction($instructions, $format = "yml")
    {
        return parent::exportAction($instructions, $format);
    }

    /**
     * @Route("/Test/{object_ids}/instructions/export", name="Test_export_instructions")
     * @param $object_ids
     * @return Response
     */
    public function exportInstructionsAction($object_ids)
    {
        return parent::exportInstructionsAction($object_ids);
    }

    /**
     * @Route("/Test/import", name="Test_import", methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function scheduleImportAction(Request $request)
    {
        return parent::scheduleImportAction($request);
    }

    /**
     * @Route("/Test/import/status", name="Test_pre_import_status")
     * @param Request $request
     * @return Response
     */
    public function preImportStatusAction(Request $request)
    {
        return parent::preImportStatusAction($request);
    }

    /**
     * @Route("/Test/{object_id}/node/add", name="Test_add_node", methods={"POST"})
     * @param Request $request
     * @param $object_id
     * @return Response
     */
    public function addNodeAction(Request $request, $object_id)
    {
        if (!$this->service->canBeModified($object_id, $request->get("objectTimestamp"), $errorMessages)) {
            $response = new Response(json_encode(array("result" => 1, "errors" => $this->trans($errorMessages))));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $result = $this->service->addFlowNode(
            $request->get("type"),
            $request->get("posX"),
            $request->get("posY"),
            $this->service->get($request->get("flowTest")),
            $this->service->get($request->get("sourceTest")),
            "",
            false);
        return $this->getSaveResponse($result);
    }

    /**
     * @Route("/Test/{object_id}/connection/add", name="Test_add_connection", methods={"POST"})
     * @param Request $request
     * @param $object_id
     * @return Response
     */
    public function addNodeConnectionAction(Request $request, $object_id)
    {
        if (!$this->service->canBeModified($object_id, $request->get("objectTimestamp"), $errorMessages)) {
            $response = new Response(json_encode(array("result" => 1, "errors" => $this->trans($errorMessages))));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $result = $this->service->addFlowConnection(
            $this->service->get($request->get("flowTest")),
            $request->get("sourceNode"),
            $request->get("sourcePort") ? $request->get("sourcePort") : null,
            $request->get("destinationNode"),
            $request->get("destinationPort") ? $request->get("destinationPort") : null,
            $request->get("returnFunction"),
            $request->get("default") == "1",
            false);
        return $this->getSaveResponse($result);
    }

    /**
     * @Route("/Test/node/move", name="Test_move_node", methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function moveNodeAction(Request $request)
    {
        if (!$this->service->canBeModified($request->get("object_id"), $request->get("objectTimestamp"), $errorMessages)) {
            $response = new Response(json_encode(array("result" => 1, "errors" => $this->trans($errorMessages))));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $nodes = json_decode($request->get("nodes"), true);
        $this->service->moveFlowNode($nodes);

        $response = new Response(json_encode([
            "result" => 0,
            "objectTimestamp" => time()
        ]));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/Test/{object_id}/node/paste", name="Test_paste_nodes", methods={"POST"})
     * @param Request $request
     * @param $object_id
     * @return Response
     */
    public function pasteNodesAction(Request $request, $object_id)
    {
        if (!$this->service->canBeModified($object_id, $request->get("objectTimestamp"), $errorMessages)) {
            $response = new Response(json_encode(array("result" => 1, "errors" => $this->trans($errorMessages))));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $result = $this->service->pasteNodes(
            $this->service->get($object_id),
            json_decode($request->get("nodes"), true),
            false);
        return $this->getSaveResponse($result);
    }
}
