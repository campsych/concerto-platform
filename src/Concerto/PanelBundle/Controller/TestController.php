<?php

namespace Concerto\PanelBundle\Controller;

use Concerto\PanelBundle\Service\FileService;
use Concerto\PanelBundle\Service\TestService;
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

    public function __construct($environment, EngineInterface $templating, TestService $service, TranslatorInterface $translator, TestWizardService $testWizardService, ImportService $importService, ExportService $exportService, UserService $userService, FileService $fileService)
    {
        parent::__construct($environment, $templating, $service, $translator, $importService, $exportService, $fileService);

        $this->entityName = self::ENTITY_NAME;
        $this->exportFilePrefix = self::EXPORT_FILE_PREFIX;

        $this->testWizardService = $testWizardService;
        $this->userService = $userService;
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
        return parent::collectionAction($format);
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
            $request->get("serializedVariables")
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
    public function importAction(Request $request)
    {
        return parent::importAction($request);
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
     * @Route("/Test/node/{node_ids}/remove", name="Test_remove_node", methods={"POST"})
     * @param $node_ids
     * @return Response
     */
    public function removeNodeAction($node_ids)
    {
        $collections = false;
        $result = $this->service->removeFlowNode($node_ids, $collections);

        $errors = array();
        for ($a = 0; $a < count($result["results"]); $a++) {
            for ($i = 0; $i < count($result["results"][$a]['errors']); $i++) {
                $errors[] = "#" . $result["results"][$a]["object"]->getId() . ": " . $result["results"][$a]["object"]->getName() . " - " . $this->translator->trans($result["results"][$a]['errors'][$i]);
            }
        }

        if (count($errors) > 0) {
            $response_array = array("result" => 1, "errors" => $errors);
            if ($collections)
                $response_array["collections"] = $result["collections"];
            $response = new Response(json_encode($response_array));
        } else {
            $response_array = array("result" => 0, "object_ids" => $node_ids);
            if ($collections)
                $response_array["collections"] = $result["collections"];
            $response = new Response(json_encode($response_array));
        }
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/Test/{object_id}/connection/add", name="Test_add_connection", methods={"POST"})
     * @param Request $request
     * @param $object_id
     * @return Response
     */
    public function addNodeConnectionAction(Request $request, $object_id)
    {
        $result = $this->service->addFlowConnection(
            $this->service->get($request->get("flowTest")),
            $request->get("sourceNode"),
            $request->get("sourcePort") ? $request->get("sourcePort") : null,
            $request->get("destinationNode"),
            $request->get("destinationPort") ? $request->get("destinationPort") : null,
            $request->get("returnFunction"),
            false,
            $request->get("default") == "1",
            false);
        return $this->getSaveResponse($result);
    }

    /**
     * @Route("/Test/connection/{connection_id}/remove", name="Test_remove_connection", methods={"POST"})
     * @param $connection_id
     * @return Response
     */
    public function removeNodeConnectionAction($connection_id)
    {
        $collections = false;
        $result = $this->service->removeFlowConnection($connection_id, $collections);

        $errors = array();
        for ($i = 0; $i < count($result['errors']); $i++) {
            $errors[] = "#" . $result["object"]->getId() . ": " . $result["object"]->getName() . " - " . $this->translator->trans($result['errors'][$i]);
        }
        if (count($errors) > 0) {
            $response_array = array("result" => 1, "errors" => $errors);
            if ($collections)
                $response_array["collections"] = $result["collections"];
            $response = new Response(json_encode($response_array));
        } else {
            $response_array = array("result" => 0, "object_ids" => $connection_id);
            if ($collections)
                $response_array["collections"] = $result["collections"];
            $response = new Response(json_encode($response_array));
        }
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/Test/node/move", name="Test_move_node", methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function moveNodeAction(Request $request)
    {
        $nodes = json_decode($request->get("nodes"), true);
        $result = $this->service->moveFlowNode($nodes);

        $response = new Response(json_encode(array("result" => 0)));
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
        $result = $this->service->pasteNodes(
            $this->service->get($object_id),
            json_decode($request->get("nodes"), true),
            false);
        return $this->getSaveResponse($result);
    }
}
