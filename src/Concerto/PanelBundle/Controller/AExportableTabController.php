<?php

namespace Concerto\PanelBundle\Controller;

use Concerto\PanelBundle\Service\FileService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Concerto\PanelBundle\Service\AExportableSectionService;
use Symfony\Component\HttpFoundation\Request;
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

    public function __construct($environment, EngineInterface $templating, AExportableSectionService $service, TranslatorInterface $translator, ImportService $importService, ExportService $exportService, FileService $fileService)
    {
        parent::__construct($templating, $service, $translator);

        $this->environment = $environment;
        $this->importService = $importService;
        $this->exportService = $exportService;
        $this->fileService = $fileService;
    }

    public function preImportStatusAction(Request $request)
    {
        $filePath = realpath($this->fileService->getPrivateUploadDirectory()) . "/" . $this->fileService->canonicalizePath(basename($request->get("file")));
        $instructions = $this->importService->getPreImportStatusFromFile(
            $filePath,
            $errorMessages
        );

        $response = new Response(json_encode(array(
            "result" => $instructions !== false ? 0 : 1,
            "errors" => $this->trans($errorMessages),
            "status" => $instructions
        )));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function scheduleImportAction(Request $request)
    {
        $file = $this->fileService->getPrivateUploadDirectory() . $request->get("file");
        $instructions = $request->get("instructions");
        $scheduled = $request->get("instant") != 1;

        $success = $this->importService->scheduleTaskImportContent($file, $instructions, $scheduled, $output, $errorMessages);

        $response = new Response(json_encode(array("result" => $success ? 0 : 1, "errors" => $this->trans($errorMessages))));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function exportInstructionsAction($object_ids)
    {
        $instructions = $this->exportService->getInitialExportInstructions($this->entityName, $object_ids);
        $response = new Response(json_encode(array(
            "result" => 0,
            "instructions" => $instructions
        )));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function exportAction($instructions, $format = "yml")
    {
        $fullInstructions = $this->exportService->decompactExportInstructions(json_decode($instructions, true));

        $response = new Response($this->exportService->exportToFile($this->entityName, $fullInstructions, $format));
        $response->headers->set('Content-Type', 'application/x-download');
        $response->headers->set(
            'Content-Disposition', 'attachment; filename="' . $this->service->getExportFileName($this->exportFilePrefix, $fullInstructions, $format) . '"'
        );
        return $response;
    }

    public function copyAction(Request $request, $object_id)
    {
        $newObject = null;
        $copySuccessful = $this->importService->copy(
            $this->entityName,
            $object_id,
            $request->get("name"),
            $errorMessages,
            $newObject
        );

        $response = new Response(json_encode(array("result" => $copySuccessful ? 0 : 1, "errors" => $this->trans($errorMessages), "object" => $newObject)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function toggleLock(Request $request, $object_id)
    {
        $timestamp = $request->get("objectTimestamp");
        if (!$this->service->canBeModified($object_id, $timestamp, $errorMessages)) {
            $response = new Response(json_encode(array("result" => 1, "errors" => $this->trans($errorMessages))));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $this->service->toggleLock($object_id);
        $response = new Response(json_encode(array("result" => 0)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}
