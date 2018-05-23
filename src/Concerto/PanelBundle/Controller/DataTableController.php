<?php

namespace Concerto\PanelBundle\Controller;

use Concerto\PanelBundle\Service\FileService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Concerto\PanelBundle\DAO\DAOUnsupportedOperationException;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Concerto\PanelBundle\Service\DataTableService;
use Concerto\PanelBundle\Service\ImportService;
use Concerto\PanelBundle\Service\ExportService;
use Concerto\PanelBundle\Service\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Route("/admin")
 * @Security("has_role('ROLE_TABLE') or has_role('ROLE_SUPER_ADMIN')")
 */
class DataTableController extends AExportableTabController
{

    const ENTITY_NAME = "DataTable";
    const EXPORT_FILE_PREFIX = "DataTable_";

    private $userService;

    public function __construct($environment, EngineInterface $templating, DataTableService $service, TranslatorInterface $translator, TokenStorageInterface $securityTokenStorage, ImportService $importService, ExportService $exportService, UserService $userService, FileService $fileService)
    {
        parent::__construct($environment, $templating, $service, $translator, $securityTokenStorage, $importService, $exportService, $fileService);

        $this->entityName = self::ENTITY_NAME;
        $this->exportFilePrefix = self::EXPORT_FILE_PREFIX;

        $this->userService = $userService;
    }

    /**
     * @Route("/DataTable/fetch/{object_id}/{format}", name="DataTable_object", defaults={"format":"json"})
     * @param $object_id
     * @param string $format
     * @return Response
     */
    public function objectAction($object_id, $format)
    {
        return parent::objectAction($object_id, $format);
    }

    /**
     * @Route("/DataTable/collection/{format}", name="DataTable_collection", defaults={"format":"json"})
     * @param string $format
     * @return Response
     */
    public function collectionAction($format)
    {
        return parent::collectionAction($format);
    }

    /**
     * @Route("/DataTable/form/{action}", name="DataTable_form", defaults={"action":"edit"})
     * @param string $action
     * @param array $params
     * @return Response
     */
    public function formAction($action = "edit", $params = array())
    {
        return parent::formAction($action, $params);
    }

    /**
     * @Route("/DataTable/{object_id}/save", name="DataTable_save")
     * @Method(methods={"POST"})
     * @param Request $request
     * @param $object_id
     * @return Response
     */
    public function saveAction(Request $request, $object_id)
    {
        $result = $this->service->save(
            $this->securityTokenStorage->getToken()->getUser(), //
            $object_id, //
            $request->get("name"), //
            $request->get("description"), //
            $request->get("accessibility"), //
            $request->get("archived") === "1", //
            $this->userService->get($request->get("owner")), //
            $request->get("groups") //
        );
        return $this->getSaveResponse($result);
    }

    /**
     * @Route("/DataTable/{object_id}/copy", name="DataTable_copy")
     * @Method(methods={"POST"})
     * @param Request $request
     * @param $object_id
     * @return Response
     */
    public function copyAction(Request $request, $object_id)
    {
        return parent::copyAction($request, $object_id);
    }

    /**
     * @Route("/DataTable/{object_ids}/delete", name="DataTable_delete")
     * @Method(methods={"POST"})
     * @param string $object_ids
     * @return Response
     */
    public function deleteAction($object_ids)
    {
        return parent::deleteAction($object_ids);
    }

    /**
     * @Route("/DataTable/{table_id}/columns/collection", name="DataTable_columns_collection")
     * @param $table_id
     * @return Response
     */
    public function columnsCollectionAction($table_id)
    {
        $result_data = $this->service->getColumns($table_id);

        $response = new Response(json_encode($result_data));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/DataTable/fetch/{table_id}/column/{column_name}", name="DataTable_column_object")
     * @param $table_id
     * @param string $column_name
     * @return Response
     */
    public function fetchColumnAction($table_id, $column_name)
    {
        $response = new Response(json_encode($this->service->getColumn($table_id, $column_name)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    protected function getStreamingDataCollectionResponse($table_id, $prefixed = 0)
    {
        set_time_limit(0);
        $response = new StreamedResponse();
        $response->setCallback(function () use ($table_id, $prefixed) {
            $this->service->streamJsonData($table_id, $prefixed == 1);
        });
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/DataTable/{table_id}/data/collection/{prefixed}", name="DataTable_data_collection", defaults={"prefixed":0})
     * @param Request $request
     * @param $table_id
     * @param int $prefixed
     * @return Response
     */
    public function dataCollectionAction(Request $request, $table_id, $prefixed = 0)
    {
        $filters = $request->get("filters");

        $result_data = array(
            'content' => $this->service->getFilteredData($table_id, $prefixed, $filters),
            'count' => $this->service->countFilteredData($table_id, $filters)
        );

        $response = new Response(json_encode($result_data));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/DataTable/{table_id}/data/csv/{name}", name="DataTable_data_collection_csv")
     * @param Request $request
     * @param $table_id
     * @return Response
     */
    public function streamedCsvDataCollectionAction(Request $request, $table_id)
    {
        $name = $request->get("name");

        $response = new StreamedResponse();
        $response->setCallback(function () use ($table_id) {
            $this->service->streamCsvData($table_id);
        });
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('X-Accel-Buffering', 'no');
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $name
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * @Route("/DataTable/{table_id}/data/section", name="DataTable_data_section")
     * @param $table_id
     * @return Response
     */
    public function dataSectionAction($table_id)
    {
        return $this->templating->renderResponse("ConcertoPanelBundle:DataTable:data_section.html.twig", array(
            "table" => $this->service->get($table_id),
            "columns" => $this->service->getColumns($table_id)
        ));
    }

    /**
     * @Route("/DataTable/{table_id}/column/{column_names}/delete", name="DataTable_column_delete")
     * @Method(methods={"POST"})
     * @param $table_id
     * @param string $column_names
     * @return Response
     */
    public function deleteColumnAction($table_id, $column_names)
    {
        $this->service->deleteColumns($table_id, $column_names);
        $response = new Response(json_encode(array("result" => 0)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/DataTable/{table_id}/row/{row_ids}/delete", name="DataTable_row_delete")
     * @Method(methods={"POST"})
     * @param $table_id
     * @param string $row_ids
     * @return Response
     */
    public function deleteRowAction($table_id, $row_ids)
    {
        $this->service->deleteRows($table_id, $row_ids);
        $response = new Response(json_encode(array("result" => 0)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/DataTable/{table_id}/truncate", name="DataTable_truncate")
     * @Method(methods={"POST"})
     * @param $table_id
     * @return Response
     */
    public function truncateAction($table_id)
    {
        $this->service->truncate($table_id);
        $response = new Response(json_encode(array("result" => 0)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/DataTable/{table_id}/column/{column_name}/save", name="DataTable_column_save")
     * @Method(methods={"POST"})
     * @param Request $request
     * @param $table_id
     * @param string $column_name
     * @return Response
     */
    public function saveColumnAction(Request $request, $table_id, $column_name)
    {
        try {
            $errors = $this->service->saveColumn($table_id, $column_name, $request->get("name"), $request->get("type"));
            if (count($errors) > 0) {
                for ($i = 0; $i < count($errors); $i++) {
                    $errors[$i] = $this->translator->trans($errors[$i]);
                }
                $response = new Response(json_encode(array("result" => 1, "errors" => $errors)));
            } else {
                $response = new Response(json_encode(array("result" => 0)));
            }
        } catch (DAOUnsupportedOperationException $exc) {
            $response = new Response(json_encode(array("result" => 2, "errors" => array($this->translator->trans('errors.table.column.conversion')))));
        }
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/DataTable/{table_id}/row/insert", name="DataTable_row_insert")
     * @Method(methods={"POST"})
     * @param $table_id
     * @return Response
     */
    public function insertRowAction($table_id)
    {
        $this->service->insertRow($table_id);
        $response = new Response(json_encode(array("result" => 0)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/DataTable/{table_id}/row/{row_id}/update/{prefixed}", name="DataTable_row_update", defaults={"prefixed":0})
     * @Method(methods={"POST"})
     * @param Request $request
     * @param $table_id
     * @param $row_id
     * @param int $prefixed
     * @return Response
     */
    public function updateRowAction(Request $request, $table_id, $row_id, $prefixed = 0)
    {
        $this->service->updateRow($table_id, $row_id, $request->get("values"), $prefixed == 1);
        $response = new Response(json_encode(array("result" => 0)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/DataTable/{object_ids}/export/{format}", name="DataTable_export", defaults={"format":"compressed"})
     * @param $object_ids
     * @param string $format
     * @return Response
     */
    public function exportAction($object_ids, $format = ExportService::FORMAT_COMPRESSED)
    {
        return parent::exportAction($object_ids, $format);
    }

    /**
     * @Route("/DataTable/import", name="DataTable_import")
     * @Method(methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function importAction(Request $request)
    {
        return parent::importAction($request);
    }

    /**
     * @Route("/DataTable/import/status", name="DataTable_pre_import_status")
     * @param Request $request
     * @return Response
     */
    public function preImportStatusAction(Request $request)
    {
        return parent::preImportStatusAction($request);
    }

    /**
     * @Route("/DataTable/{table_id}/csv/{restructure}/{header}/{delimiter}/{enclosure}/import", name="DataTable_csv_import")
     * @Method(methods={"POST"})
     * @param Request $request
     * @param $table_id
     * @param $restructure
     * @param $header
     * @param $delimiter
     * @param $enclosure
     * @return Response
     */
    public function importCsvAction(Request $request, $table_id, $restructure, $header, $delimiter, $enclosure)
    {
        try {
            $this->service->importFromCsv(
                $table_id,
                $this->fileService->getPrivateUploadDirectory() . $request->get("file"),
                $restructure === "1",
                $header === "1",
                $delimiter,
                $enclosure
            );
        } catch (\Exception $ex) {
            $response = new Response(json_encode(array("result" => 1, "errors" => array($ex->getMessage()))));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
        $response = new Response(json_encode(array("result" => 0)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

}
