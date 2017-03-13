<?php

namespace Concerto\APIBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Concerto\APIBundle\Service\AModelService;
use Concerto\PanelBundle\Service\AdministrationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AAPIController {

    protected $request;
    protected $service;
    protected $administrationService;

    public function __construct(Request $request, AModelService $service, AdministrationService $administrationService) {
        $this->request = $request;
        $this->service = $service;
        $this->administrationService = $administrationService;
    }

    /**
     * @Route("/")
     * @Method({"GET","POST","PUT"})
     */
    public function collectionAction() {
        if (!$this->administrationService->isApiEnabled())
            return new Response("API disabled", Response::HTTP_FORBIDDEN);

        switch ($this->request->getMethod()) {
            case "GET": return $this->getCollection();
            case "PUT":
            case "POST": return $this->insertObject();
        }
    }

    /**
     * @Route("/{id}")
     * @Method({"GET","POST","PUT","DELETE"})
     */
    public function objectAction($id) {
        if (!$this->administrationService->isApiEnabled())
            return new Response("API disabled", Response::HTTP_FORBIDDEN);
        
        switch ($this->request->getMethod()) {
            case "GET": return $this->getObject($id);
            case "PUT":
            case "POST": return $this->updateObject($id);
            case "DELETE": return $this->deleteObject($id);
        }
    }

    private function getObject($id) {
        $format = $this->request->get("format") ? $this->request->get("format") : "json";
        $object = $this->service->get($id, $format);
        if ($object === null) {
            return new Response("Object not found", Response::HTTP_NOT_FOUND);
        } else {
            $response = new Response($object, Response::HTTP_OK);
            $response->headers->set('Content-Type', 'application/' . $format);
            return $response;
        }
    }

    private function getCollection() {
        $format = $this->request->get("format") ? $this->request->get("format") : "json";
        $filter = $this->request->query->all();
        $response = new Response($this->service->getCollection($filter, $format), Response::HTTP_OK);
        $response->headers->set('Content-Type', 'application/' . $format);
        return $response;
    }

    private function updateObject($id) {
        if (strpos($this->request->getContentType(), "json") === false) {
            return new Response("Content-Type: application/json expected", Response::HTTP_BAD_REQUEST);
        }
        $format = $this->request->get("format") ? $this->request->get("format") : "json";
        $object = $this->service->get($id, null);
        if ($object === null) {
            return new Response("Object not found", Response::HTTP_NOT_FOUND);
        } else {
            $newSerializedObject = $this->request->getContent();
            $result = $this->service->update($object, $newSerializedObject, $format);
            if ($result["result"]) {
                $response = new Response($result["result"], Response::HTTP_OK);
                $response->headers->set('Content-Type', 'application/' . $format);
                return $response;
            } else {
                $errors_string = (string) $result["errors"];
                return new Response($errors_string, Response::HTTP_BAD_REQUEST);
            }
        }
    }

    private function insertObject() {
        if (strpos($this->request->getContentType(), "json") === false) {
            return new Response("Content-Type: application/json expected", Response::HTTP_BAD_REQUEST);
        }
        $format = $this->request->get("format") ? $this->request->get("format") : "json";
        $serializedObject = $this->request->getContent();
        $result = $this->service->insert($serializedObject, $format);
        if ($result["result"]) {
            $response = new Response($result["result"], Response::HTTP_OK);
            $response->headers->set('Content-Type', 'application/' . $format);
            return $response;
        } else {
            $errors_string = (string) $result["errors"];
            return new Response($errors_string, Response::HTTP_BAD_REQUEST);
        }
    }

    private function deleteObject($id) {
        if ($this->service->delete($id)) {
            return new Response("", Response::HTTP_OK);
        } else {
            return new Response("Object not found", Response::HTTP_NOT_FOUND);
        }
    }

}
