<?php

namespace Concerto\APIBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Concerto\APIBundle\Service\DataRecordService;
use Concerto\PanelBundle\Service\AdministrationService;

/**
 * @Route("/api/data/{table_id}", service="API.DataRecord_controller")
 */
class DataRecordController {

    private $request;
    private $service;
    private $administrationService; 

    public function __construct(Request $request, DataRecordService $service, AdministrationService $administrationService) {
        $this->request = $request;
        $this->service = $service;
        $this->administrationService = $administrationService;
    }

    /**
     * @Route("")
     * @Method({"GET","POST","PUT"})
     */
    public function dataCollectionAction($table_id) {
        if (!$this->administrationService->isApiEnabled())
            return new Response("API disabled", Response::HTTP_FORBIDDEN);

        switch ($this->request->getMethod()) {
            case "GET": return $this->getDataCollection($table_id);
            case "PUT":
            case "POST": return $this->insertDataObject($table_id);
        }
    }

    /**
     * @Route("/{id}")
     * @Method({"GET","POST","PUT","DELETE"})
     */
    public function dataObjectAction($table_id, $id) {
        if (!$this->administrationService->isApiEnabled())
            return new Response("API disabled", Response::HTTP_FORBIDDEN);

        switch ($this->request->getMethod()) {
            case "GET": return $this->getDataObject($table_id, $id);
            case "PUT":
            case "POST": return $this->updateDataObject($table_id, $id);
            case "DELETE": return $this->deleteDataObject($table_id, $id);
        }
    }

    private function getDataObject($table_id, $id) {
        $format = $this->request->get("format") ? $this->request->get("format") : "json";
        $data = $this->service->getData($table_id, $id, $format);

        switch ($data["response"]) {
            case Response::HTTP_NOT_FOUND:
                return new Response("Data not found", $data["response"]);
            default:
                $response = new Response($data["result"], $data["response"]);
                $response->headers->set('Content-Type', 'application/' . $format);
                return $response;
        }
    }

    private function getDataCollection($table_id) {
        $format = $this->request->get("format") ? $this->request->get("format") : "json";
        $filter = $this->request->query->all();
        $data = $this->service->getDataCollection($table_id, $filter, $format);

        switch ($data["response"]) {
            case Response::HTTP_NOT_FOUND:
                return new Response("Data not found", $data["response"]);
            case Response::HTTP_BAD_REQUEST:
                return new Response("Incorrect filter params", $data["response"]);
            default:
                $response = new Response($data["result"], $data["response"]);
                $response->headers->set('Content-Type', 'application/' . $format);
                return $response;
        }
    }

    private function updateDataObject($table_id, $id) {
        if (strpos($this->request->getContentType(), "json") === false) {
            return new Response("Content-Type: application/json expected", Response::HTTP_BAD_REQUEST);
        }
        $format = $this->request->get("format") ? $this->request->get("format") : "json";
        $newSerializedData = $this->request->getContent();
        $data = $this->service->updateData($table_id, $id, $newSerializedData, $format);

        switch ($data["response"]) {
            case Response::HTTP_NOT_FOUND:
                return new Response("Data not found", $data["response"]);
            case Response::HTTP_BAD_REQUEST:
                return new Response("Incorrect fields", $data["response"]);
            default:
                $response = new Response($data["result"], $data["response"]);
                $response->headers->set('Content-Type', 'application/' . $format);
                return $response;
        }
    }

    private function insertDataObject($table_id) {
        if (strpos($this->request->getContentType(), "json") === false) {
            return new Response("Content-Type: application/json expected", Response::HTTP_BAD_REQUEST);
        }
        $format = $this->request->get("format") ? $this->request->get("format") : "json";
        $serializedData = $this->request->getContent();
        $data = $this->service->insertData($table_id, $serializedData, $format);

        switch ($data["response"]) {
            case Response::HTTP_NOT_FOUND:
                return new Response("Data not found", $data["response"]);
            case Response::HTTP_BAD_REQUEST:
                return new Response("Incorrect fields", $data["response"]);
            default:
                $response = new Response($data["result"], $data["response"]);
                $response->headers->set('Content-Type', 'application/' . $format);
                return $response;
        }
    }

    private function deleteDataObject($table_id, $id) {
        $data = $this->service->deleteData($table_id, $id);

        switch ($data["response"]) {
            case Response::HTTP_NOT_FOUND:
                return new Response("Data not found", $data["response"]);
            default:
                return new Response($data["result"], $data["response"]);
        }
    }

}
