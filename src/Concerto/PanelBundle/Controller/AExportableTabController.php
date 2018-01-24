<?php

namespace Concerto\PanelBundle\Controller;

use Concerto\PanelBundle\Service\FileService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Concerto\PanelBundle\Service\AExportableSectionService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Concerto\PanelBundle\Service\ImportService;
use Concerto\PanelBundle\Service\ExportService;

abstract class AExportableTabController extends ASectionController
{
    protected $environment;
    protected $exportFilePrefix;
    protected $importService;
    protected $exportService;
    protected $fileService;

    public function __construct($environment, EngineInterface $templating, AExportableSectionService $service, TranslatorInterface $translator, TokenStorageInterface $securityTokenStorage, ImportService $importService, ExportService $exportService, FileService $fileService)
    {
        parent::__construct($templating, $service, $translator, $securityTokenStorage);

        $this->environment = $environment;
        $this->importService = $importService;
        $this->exportService = $exportService;
        $this->fileService = $fileService;
    }

    public function preImportStatusAction(Request $request)
    {
        $result = $this->importService->getPreImportStatusFromFile(
            $this->fileService->getPrivateUploadDirectory() . $request->get("file"),
            $request->get("name"));

        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function importAction(Request $request)
    {
        $result = $this->importService->importFromFile(
            $this->securityTokenStorage->getToken()->getUser(),
            $this->fileService->getPrivateUploadDirectory() . $request->get("file"),
            json_decode($request->get("instructions"), true),
            false);
        $errors = array();
        $show_index = 0;
        for ($j = 0; $j < count($result["import"]); $j++) {
            $r = $result["import"][$j];

            if (array_key_exists("entity", $r) && get_class($r["entity"]) == $this->entityName)
                $show_index = $j;

            if (!array_key_exists("errors", $r))
                continue;
            for ($i = 0; $i < count($r['errors']); $i++) {
                $errors[] = $r["source"]["class_name"] . "#" . $r["source"]["id"] . ": " . $this->translator->trans($r['errors'][$i]);
            }
        }
        if ($result["result"] == 1) {
            $response = new Response(json_encode(array("result" => 1, "errors" => $errors)));
        } else if ($result["result"] == 0) {
            $response = new Response(json_encode(array("result" => 0, "object" => $result["import"][$show_index]['entity'], "object_id" => $result["import"][$show_index]['entity']->getId())));
        } else if ($result["result"] == 2) {
            $response = new Response(json_encode(array("result" => 2)));
        }
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function exportAction($object_ids, $format = ExportService::FORMAT_COMPRESSED)
    {
        $response = new Response($this->exportService->exportToFile($this->entityName, $object_ids, $format));
        $response->headers->set('Content-Type', 'application/x-download');
        $response->headers->set(
            'Content-Disposition', 'attachment; filename="' . $this->service->getExportFileName($this->exportFilePrefix, $object_ids, $format) . '"'
        );
        return $response;
    }

    public function copyAction(Request $request, $object_id)
    {
        $result = $this->importService->copy(
            $this->entityName,
            $this->securityTokenStorage->getToken()->getUser(),
            $object_id,
            $request->get("name")
        );
        $errors = array();
        $show_index = 0;
        for ($j = 0; $j < count($result["import"]); $j++) {
            $r = $result["import"][$j];

            if (array_key_exists("entity", $r) && json_decode(json_encode($r["entity"]), true)["class_name"] == $this->entityName)
                $show_index = $j;

            if (!array_key_exists("errors", $r))
                continue;
            for ($i = 0; $i < count($r['errors']); $i++) {
                $errors[] = $r["source"]["class_name"] . "#" . $r["source"]["id"] . ": " . $this->translator->trans($r['errors'][$i]);
            }
        }
        if (count($errors) > 0) {
            $response = new Response(json_encode(array("result" => 1, "errors" => $errors)));
        } else {
            $response = new Response(json_encode(array("result" => 0, "object" => $result["import"][$show_index]['entity'], "object_id" => $result["import"][$show_index]['entity']->getId())));
        }
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

}
