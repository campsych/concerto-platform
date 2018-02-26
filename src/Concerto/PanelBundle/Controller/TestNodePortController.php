<?php

namespace Concerto\PanelBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Concerto\PanelBundle\Service\TestNodeService;
use Concerto\PanelBundle\Service\TestNodePortService;
use Concerto\PanelBundle\Service\TestVariableService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
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

    public function __construct(EngineInterface $templating, TestNodePortService $portService, TestNodeService $nodeService, TestVariableService $variableService, TranslatorInterface $translator, TokenStorageInterface $securityTokenStorage)
    {
        parent::__construct($templating, $portService, $translator, $securityTokenStorage);

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
     * @Route("/TestNodePort/{object_ids}/delete", name="TestNodePort_delete")
     * @Method(methods={"POST"})
     * @param string $object_ids
     * @return Response
     */
    public function deleteAction($object_ids)
    {
        return parent::deleteAction($object_ids);
    }

    /**
     * @Route("/TestNodePort/{object_id}/save", name="TestNodePort_save")
     * @Method(methods={"POST"})
     * @param Request $request
     * @param $object_id
     * @return Response
     */
    public function saveAction(Request $request, $object_id)
    {
        $node = $this->testNodeService->get($request->get("node"));
        $variable = $this->testVariableService->get($request->get("variable"));
        $default = $request->get("default") === "1";
        $value = $request->get("value");
        $string = $request->get("string") === "1";

        $result = $this->service->save(
            $this->securityTokenStorage->getToken()->getUser(),
            $object_id,
            $node,
            $variable,
            $default,
            $value,
            $string);
        return $this->getSaveResponse($result);
    }

    /**
     * @Route("/TestNodePort/save", name="TestNodePort_save_collection")
     * @Method(methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function saveCollectionAction(Request $request)
    {
        $result = $this->service->saveCollection(
            $this->securityTokenStorage->getToken()->getUser(),
            $request->get("serializedCollection")
        );
        if (count($result["errors"]) > 0) {
            for ($i = 0; $i < count($result["errors"]); $i++) {
                $result["errors"][$i] = $this->translator->trans($result["errors"][$i]);
            }
            $response = new Response(json_encode(array("result" => 1, "errors" => $result["errors"])));
        } else {
            $response = new Response(json_encode(array("result" => 0, "object" => null)));
        }
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

}
