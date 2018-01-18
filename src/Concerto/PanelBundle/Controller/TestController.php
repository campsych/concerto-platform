<?php

namespace Concerto\PanelBundle\Controller;

use Concerto\PanelBundle\Service\FileService;
use Symfony\Component\HttpFoundation\Response;
use Concerto\PanelBundle\Service\TestWizardService;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Concerto\PanelBundle\Service\AExportableSectionService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Concerto\PanelBundle\Service\ImportService;
use Concerto\PanelBundle\Service\ExportService;
use Concerto\PanelBundle\Service\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Security("has_role('ROLE_TEST') or has_role('ROLE_SUPER_ADMIN')")
 */
class TestController extends AExportableTabController
{

    const ENTITY_NAME = "Test";
    const EXPORT_FILE_PREFIX = "Test_";

    private $testWizardService;
    private $userService;

    public function __construct($environment, EngineInterface $templating, AExportableSectionService $service, TranslatorInterface $translator, TokenStorage $securityTokenStorage, TestWizardService $testWizardService, ImportService $importService, ExportService $exportService, UserService $userService, FileService $fileService)
    {
        parent::__construct($environment, $templating, $service, $translator, $securityTokenStorage, $importService, $exportService, $fileService);

        $this->entityName = self::ENTITY_NAME;
        $this->exportFilePrefix = self::EXPORT_FILE_PREFIX;

        $this->testWizardService = $testWizardService;
        $this->userService = $userService;
    }

    public function updateDependentAction($object_id)
    {
        $result = $this->service->updateDependentTests(
            $this->securityTokenStorage->getToken()->getUser(), //
            $this->service->get($object_id)
        );
        $errors = array();
        foreach ($result as $r) {
            for ($i = 0; $i < count($r['errors']); $i++) {
                $errors[] = "#" . $r["object"]->getId() . ": " . $r["object"]->getName() . " - " . $this->translator->trans($r['errors'][$i]);
            }
        }
        if (count($errors) > 0) {
            $response = new Response(json_encode(array("result" => 1, "errors" => $errors)));
        } else {
            $response = new Response(json_encode(array("result" => 0, "object_id" => $object_id)));
        }
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

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
            $request->get("visibility"),
            $request->get("type"),
            $request->get("code"),
            $this->testWizardService->get($request->get("sourceWizard"), false),
            $request->get("slug"),
            $request->get("serializedVariables")
        );
        return $this->getSaveResponse($result);
    }

    public function addNodeAction(Request $request, $object_id)
    {
        $result = $this->service->addFlowNode(
            $this->securityTokenStorage->getToken()->getUser(),
            $request->get("type"),
            $request->get("posX"),
            $request->get("posY"),
            $this->service->get($request->get("flowTest")),
            $this->service->get($request->get("sourceTest")),
            "",
            false);
        return $this->getSaveResponse($result);
    }

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

    public function addNodeConnectionAction(Request $request, $object_id)
    {
        $result = $this->service->addFlowConnection(
            $this->securityTokenStorage->getToken()->getUser(),
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

    public function moveNodeAction(Request $request)
    {
        $nodes = json_decode($request->get("nodes"), true);
        $result = $this->service->moveFlowNode($nodes);

        $response = new Response(json_encode(array("result" => 0)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function pasteNodesAction(Request $request, $object_id)
    {
        $result = $this->service->pasteNodes(
            $this->securityTokenStorage->getToken()->getUser(),
            $this->service->get($object_id),
            json_decode($request->get("nodes"), true),
            false);
        return $this->getSaveResponse($result);
    }

    public function exportNodeAction($object_ids, $format = ExportService::FORMAT_COMPRESSED)
    {
        $response = new Response($this->exportService->exportNodeToFile($object_ids, $format));
        $ext = ($format == ExportService::FORMAT_COMPRESSED) ? 'concerto' : 'concerto.json';
        $name = "TestNode_" . $object_ids . '.' . $ext;
        $response->headers->set('Content-Type', 'application/x-download');
        $response->headers->set(
            'Content-Disposition', 'attachment; filename="' . $name . '"'
        );
        return $response;
    }

}
