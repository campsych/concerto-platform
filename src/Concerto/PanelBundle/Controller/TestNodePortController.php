<?php

namespace Concerto\PanelBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Concerto\PanelBundle\Service\TestNodeService;
use Concerto\PanelBundle\Service\TestNodePortService;
use Concerto\PanelBundle\Service\TestVariableService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Security("has_role('ROLE_TEST') or has_role('ROLE_SUPER_ADMIN')")
 */
class TestNodePortController extends ASectionController
{

    const ENTITY_NAME = "TestNodePort";

    private $testNodeService;
    private $testVariableService;

    public function __construct(EngineInterface $templating, TestNodePortService $portService, TestNodeService $nodeService, TestVariableService $variableService, TranslatorInterface $translator, TokenStorage $securityTokenStorage)
    {
        parent::__construct($templating, $portService, $translator, $securityTokenStorage);

        $this->entityName = self::ENTITY_NAME;

        $this->testNodeService = $nodeService;
        $this->testVariableService = $variableService;
    }

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
