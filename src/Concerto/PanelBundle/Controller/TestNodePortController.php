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
class TestNodePortController extends ASectionController {

    const ENTITY_NAME = "TestNodePort";

    private $request;
    private $testNodeService;
    private $testVariableService;

    public function __construct(EngineInterface $templating, TestNodePortService $portService, TestNodeService $nodeService, TestVariableService $variableService, Request $request, TranslatorInterface $translator, TokenStorage $securityTokenStorage) {
        parent::__construct($templating, $portService, $translator, $securityTokenStorage);

        $this->entityName = self::ENTITY_NAME;

        $this->testNodeService = $nodeService;
        $this->testVariableService = $variableService;
        $this->request = $request;
    }

    public function saveAction($object_id) {
        $node = $this->testNodeService->get($this->request->get("node"));
        $variable = $this->testVariableService->get($this->request->get("variable"));
        $default = $this->request->get("default") === "1";
        $value = $this->request->get("value");
        $string = $this->request->get("string") === "1";

        $result = $this->service->save(//
                $this->securityTokenStorage->getToken()->getUser(), //
                $object_id, //
                $node, //
                $variable, //
                $default, //
                $value, //
                $string);
        return $this->getSaveResponse($result);
    }

    public function saveCollectionAction() {
        $result = $this->service->saveCollection(
                $this->securityTokenStorage->getToken()->getUser(), //
                $this->request->get("serializedCollection") //
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
