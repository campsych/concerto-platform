<?php

namespace Concerto\PanelBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Concerto\PanelBundle\Service\TestNodeService;
use Concerto\PanelBundle\Service\TestNodePortService;
use Concerto\PanelBundle\Service\TestVariableService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Route("/admin")
 * @Security("has_role('ROLE_TEST') or has_role('ROLE_SUPER_ADMIN')")
 */
class TestNodePortController extends ASectionController
{

    const ENTITY_NAME = "TestNodePort";

    private $testNodeService;
    private $testVariableService;

    public function __construct(EngineInterface $templating, TestNodePortService $portService, TestNodeService $nodeService, TestVariableService $variableService, TranslatorInterface $translator)
    {
        parent::__construct($templating, $portService, $translator);

        $this->entityName = self::ENTITY_NAME;

        $this->testNodeService = $nodeService;
        $this->testVariableService = $variableService;
    }

    /**
     * @Route("/TestNodePort/fetch/{object_id}/{format}", name="TestNodePort_object", defaults={"format":"json"})
     * @param $object_id
     * @param string $format
     * @return Response
     */
    public function objectAction($object_id, $format = "json")
    {
        return parent::objectAction($object_id, $format);
    }

    /**
     * @Route("/TestNodePort/collection/{format}", name="TestNodePort_collection", defaults={"format":"json"})
     * @param string $format
     * @return Response
     */
    public function collectionAction($format = "json")
    {
        return parent::collectionAction($format);
    }

    /**
     * @Route("/TestNodePort/{object_ids}/delete", name="TestNodePort_delete", methods={"POST"})
     * @param Request $request
     * @param string $object_ids
     * @return Response
     */
    public function deleteAction(Request $request, $object_ids)
    {
        return parent::deleteAction($request, $object_ids);
    }

    /**
     * @Route("/TestNodePort/{object_id}/save", name="TestNodePort_save", methods={"POST"})
     * @param Request $request
     * @param $object_id
     * @return Response
     */
    public function saveAction(Request $request, $object_id)
    {
        if (!$this->service->canBeModified($object_id, $request->get("objectTimestamp"), $errorMessages)) {
            $response = new Response(json_encode(array("result" => 1, "errors" => $this->trans($errorMessages))));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $node = $this->testNodeService->get($request->get("node"));
        $variable = $this->testVariableService->get($request->get("variable"));
        $default = $request->get("default") === "1";
        $value = $request->get("value");
        $string = $request->get("string") === "1";
        $type = $request->get("type");
        $dynamic = $request->get("dynamic") === "1";
        $exposed = $request->get("exposed") === "1";
        $name = $request->get("name");
        $pointer = $request->get("pointer") === "1";
        $pointerVariable = $request->get("pointerVariable");

        $result = $this->service->save(
            $object_id,
            $node,
            $variable,
            $default,
            $value,
            $string,
            $type,
            $dynamic,
            $exposed,
            $name,
            $pointer,
            $pointerVariable
        );
        return $this->getSaveResponse($result);
    }

    /**
     * @Route("/TestNodePort/save", name="TestNodePort_save_collection", methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function saveCollectionAction(Request $request)
    {
        if (!$this->testNodeService->canBeModified($request->get("node_id"), $request->get("objectTimestamp"), $errorMessages)) {
            $response = new Response(json_encode(array("result" => 1, "errors" => $this->trans($errorMessages))));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $result = $this->service->saveCollection(
            $request->get("serializedCollection")
        );

        $errors = $this->trans($result["errors"]);
        $response = new Response(json_encode([
            "result" => count($errors) > 0 ? 1 : 0,
            "errors" => $errors,
            "objectTimestamp" => time()
        ]));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/TestNodePort/{object_id}/hide", name="TestNodePort_hide", methods={"POST"})
     * @param Request $request
     * @param integer $object_id
     * @return Response
     */
    public function hideAction(Request $request, $object_id)
    {
        if (!$this->service->canBeModified($object_id, $request->get("objectTimestamp"), $errorMessages)) {
            $response = new Response(json_encode(array("result" => 1, "errors" => $this->trans($errorMessages))));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $this->service->hide($object_id);
        $response = new Response(json_encode(array("result" => 0)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}
