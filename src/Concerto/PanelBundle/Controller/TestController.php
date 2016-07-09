<?php

namespace Concerto\PanelBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Concerto\PanelBundle\Service\TestWizardService;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Concerto\PanelBundle\Service\AExportableSectionService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Concerto\PanelBundle\Service\ImportService;
use Concerto\PanelBundle\Service\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Security("has_role('ROLE_TEST') or has_role('ROLE_SUPER_ADMIN')")
 */
class TestController extends AExportableTabController {

    const ENTITY_NAME = "Test";
    const EXPORT_FILE_PREFIX = "Test_";

    private $testWizardService;
    private $userService;

    public function __construct($environment, EngineInterface $templating, AExportableSectionService $service, Request $request, TranslatorInterface $translator, TokenStorage $securityTokenStorage, TestWizardService $testWizardService, ImportService $importService, UserService $userService) {
        parent::__construct($environment, $templating, $service, $request, $translator, $securityTokenStorage, $importService);

        $this->entityName = self::ENTITY_NAME;
        $this->exportFilePrefix = self::EXPORT_FILE_PREFIX;

        $this->testWizardService = $testWizardService;
        $this->userService = $userService;
    }

    public function updateDependentAction($object_id) {
        $result = $this->service->updateDependentTests(
                $this->securityTokenStorage->getToken()->getUser(), //
                $object_id
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
                $this->request->get("visibility"), //
                $this->request->get("type"), //
                $this->request->get("code"), //
                $this->request->get("resumable"), //
                $this->testWizardService->get($this->request->get("sourceWizard"), false), //
                $this->request->get("slug"), //
                $this->request->get("serializedVariables") //
        );
        return $this->getSaveResponse($result);
    }

    public function addNodeAction($object_id) {
        $result = $this->service->addFlowNode(//
                $this->securityTokenStorage->getToken()->getUser(), //
                $this->request->get("type"), //
                $this->request->get("posX"), //
                $this->request->get("posY"), //
                $this->service->get($this->request->get("flowTest")), //
                $this->service->get($this->request->get("sourceTest")), //
                true);
        return $this->getSaveResponse($result);
    }

    public function removeNodeAction($node_ids) {
        $result = $this->service->removeFlowNode($node_ids, true);

        $errors = array();
        for ($a = 0; $a < count($result["results"]); $a++) {
            for ($i = 0; $i < count($result["results"][$a]['errors']); $i++) {
                $errors[] = "#" . $result["results"][$a]["object"]->getId() . ": " . $result["results"][$a]["object"]->getName() . " - " . $this->translator->trans($result["results"][$a]['errors'][$i]);
            }
        }

        if (count($errors) > 0) {
            $response = new Response(json_encode(array("result" => 1, "errors" => $errors, "collections" => $result["collections"])));
        } else {
            $response = new Response(json_encode(array("result" => 0, "object_ids" => $node_ids, "collections" => $result["collections"])));
        }
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function addNodeConnectionAction($object_id) {
        $result = $this->service->addFlowConnection(//
                $this->securityTokenStorage->getToken()->getUser(), //
                $this->service->get($this->request->get("flowTest")), //
                $this->request->get("sourceNode"), //
                $this->request->get("sourcePort") ? $this->request->get("sourcePort") : null, //
                $this->request->get("destinationNode"), //
                $this->request->get("destinationPort") ? $this->request->get("destinationPort") : null, //
                $this->request->get("returnFunction"), //
                false, //
                true);
        return $this->getSaveResponse($result);
    }

    public function removeNodeConnectionAction($connection_id) {
        $result = $this->service->removeFlowConnection($connection_id, true);

        $errors = array();
        for ($i = 0; $i < count($result['errors']); $i++) {
            $errors[] = "#" . $result["object"]->getId() . ": " . $result["object"]->getName() . " - " . $this->translator->trans($result['errors'][$i]);
        }
        if (count($errors) > 0) {
            $response = new Response(json_encode(array("result" => 1, "errors" => $errors, "collections" => $result["collections"])));
        } else {
            $response = new Response(json_encode(array("result" => 0, "object_ids" => $connection_id, "collections" => $result["collections"])));
        }
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function moveNodeAction() {
        $nodes = json_decode($this->request->get("nodes"), true);
        $result = $this->service->moveFlowNode($nodes);

        $response = new Response(json_encode(array("result" => 0)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

}
