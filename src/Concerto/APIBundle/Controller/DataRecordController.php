<?php

namespace Concerto\APIBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Concerto\APIBundle\Service\DataRecordService;
use Concerto\PanelBundle\Service\AdministrationService;

/**
 * @Route("/api/data/{table_id}")
 */
class DataRecordController
{

    private $service;
    private $administrationService;

    public function __construct(DataRecordService $service, AdministrationService $administrationService)
    {
        $this->service = $service;
        $this->administrationService = $administrationService;
    }

    /**
     * @Route("")
     * @Method({"GET","POST","PUT"})
     * @param Request $request
     * @param int $table_id
     * @return Response
     */
    public function dataCollectionAction(Request $request, $table_id)
    {
        if (!$this->administrationService->isApiEnabled())
            return new Response("API disabled", Response::HTTP_FORBIDDEN);

        switch ($request->getMethod()) {
            case "GET":
                return $this->getDataCollection($request, $table_id);
            case "PUT":
            case "POST":
                return $this->insertDataObject($request, $table_id);
        }
    }

    /**
     * @Route("/{id}")
     * @Method({"GET","POST","PUT","DELETE"})
     * @param Request $request
     * @param int $table_id
     * @param int $id
     * @return Response
     */
    public function dataObjectAction(Request $request, $table_id, $id)
    {
        if (!$this->administrationService->isApiEnabled())
            return new Response("API disabled", Response::HTTP_FORBIDDEN);

        switch ($request->getMethod()) {
            case "GET":
                return $this->getDataObject($request, $table_id, $id);
            case "PUT":
            case "POST":
                return $this->updateDataObject($request, $table_id, $id);
            case "DELETE":
                return $this->deleteDataObject($table_id, $id);
        }
    }

    private function getDataObject(Request $request, $table_id, $id)
    {
        $format = $request->get("format") ? $request->get("format") : "json";
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

    private function getDataCollection(Request $request, $table_id)
    {
        $format = $request->get("format") ? $request->get("format") : "json";
        $filter = $request->query->all();
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

    private function updateDataObject(Request $request, $table_id, $id)
    {
        if (strpos($request->getContentType(), "json") === false) {
            return new Response("Content-Type: application/json expected", Response::HTTP_BAD_REQUEST);
        }
        $format = $request->get("format") ? $request->get("format") : "json";
        $newSerializedData = $request->getContent();
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

    private function insertDataObject(Request $request, $table_id)
    {
        if (strpos($request->getContentType(), "json") === false) {
            return new Response("Content-Type: application/json expected", Response::HTTP_BAD_REQUEST);
        }
        $format = $request->get("format") ? $request->get("format") : "json";
        $serializedData = $request->getContent();
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

    private function deleteDataObject($table_id, $id)
    {
        $data = $this->service->deleteData($table_id, $id);

        switch ($data["response"]) {
            case Response::HTTP_NOT_FOUND:
                return new Response("Data not found", $data["response"]);
            default:
                return new Response($data["result"], $data["response"]);
        }
    }

}
