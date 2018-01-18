<?php

namespace Concerto\PanelBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Concerto\PanelBundle\Service\ASectionService;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Translation\TranslatorInterface;

abstract class ASectionController
{

    protected $entityName;
    protected $service;
    protected $templating;
    protected $translator;
    protected $securityTokenStorage;

    public function __construct(EngineInterface $templating, ASectionService $service, TranslatorInterface $translator, TokenStorage $securityTokenStorage)
    {
        $this->templating = $templating;
        $this->service = $service;
        $this->translator = $translator;
        $this->securityTokenStorage = $securityTokenStorage;
    }

    protected function getSaveResponse($result)
    {
        if (count($result["errors"]) > 0) {
            for ($i = 0; $i < count($result["errors"]); $i++) {
                $result["errors"][$i] = $this->translator->trans($result["errors"][$i]);
            }
            $result["result"] = 1;
            $response = new Response(json_encode($result));
        } else {
            $result["result"] = 0;
            $response = new Response(json_encode($result));
        }
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function deleteAction($object_ids)
    {
        $result = $this->service->delete($object_ids);
        $errors = array();
        foreach ($result as $r) {
            for ($i = 0; $i < count($r['errors']); $i++) {
                $errors[] = "#" . $r["object"]->getId() . ": " . $r["object"]->getName() . " - " . $this->translator->trans($r['errors'][$i]);
            }
        }
        if (count($errors) > 0) {
            $response = new Response(json_encode(array("result" => 1, "errors" => $errors)));
        } else {
            $response = new Response(json_encode(array("result" => 0, "object_ids" => $object_ids)));
        }
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function objectAction($object_id, $format)
    {
        return $this->templating->renderResponse("ConcertoPanelBundle::collection.$format.twig", array(
            'collection' => $this->service->get($object_id)
        ));
    }

    public function collectionAction($format)
    {
        $collection = $this->service->getAll();
        return $this->templating->renderResponse("ConcertoPanelBundle::collection.$format.twig", array(
            'collection' => $collection
        ));
    }

    public function formAction($action = "edit", $params = array())
    {
        $p = array(
            "isAddDialog" => $action === "add"
        );
        foreach ($params as $k => $v) {
            $p[$k] = $v;
        }
        return $this->templating->renderResponse("ConcertoPanelBundle:" . $this->entityName . ":form.html.twig", $p);
    }

}
