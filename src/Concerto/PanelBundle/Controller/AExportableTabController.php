<?php

namespace Concerto\PanelBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Concerto\PanelBundle\Service\AExportableSectionService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Concerto\PanelBundle\Service\ImportService;

abstract class AExportableTabController extends ASectionController {

    protected $request;
    protected $environment;
    protected $exportFilePrefix;
    protected $importService;

    public function __construct($environment, EngineInterface $templating, AExportableSectionService $service, Request $request, TranslatorInterface $translator, TokenStorage $securityTokenStorage, ImportService $importService) {
        parent::__construct($templating, $service, $translator, $securityTokenStorage);

        $this->environment = $environment;
        $this->request = $request;
        $this->importService = $importService;
    }

    public function preImportStatusAction() {
        $status = $this->importService->getPreImportStatus(
                __DIR__ . DIRECTORY_SEPARATOR .
                ".." . DIRECTORY_SEPARATOR .
                ($this->environment == "test" ? "Tests" . DIRECTORY_SEPARATOR : "") .
                "Resources" . DIRECTORY_SEPARATOR .
                "public" . DIRECTORY_SEPARATOR .
                "files" . DIRECTORY_SEPARATOR .
                $this->request->get("file"), //
                $this->request->get("name"));
        $response = new Response(json_encode(array("result" => 0, "status" => $status)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function importAction() {
        $result = $this->importService->importFromFile(
                $this->securityTokenStorage->getToken()->getUser(), //
                __DIR__ . DIRECTORY_SEPARATOR .
                ".." . DIRECTORY_SEPARATOR .
                ($this->environment == "test" ? "Tests" . DIRECTORY_SEPARATOR : "") .
                "Resources" . DIRECTORY_SEPARATOR .
                "public" . DIRECTORY_SEPARATOR .
                "files" . DIRECTORY_SEPARATOR .
                $this->request->get("file"), //
                $this->request->get("name"), //
                false);
        $errors = array();
        foreach ($result as $r) {
            if (!array_key_exists("errors", $r))
                continue;
            for ($i = 0; $i < count($r['errors']); $i++) {
                $errors[] = $r["source"]["class_name"] . "#" . $r["source"]["id"] . ": " . $this->translator->trans($r['errors'][$i]);
            }
        }
        if (count($errors) > 0) {
            $response = new Response(json_encode(array("result" => 1, "errors" => $errors)));
        } else {
            $response = new Response(json_encode(array("result" => 0, "object_id" => $result[0]['entity']->getId())));
        }
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function exportAction($object_ids, $format = AExportableSectionService::FORMAT_COMPRESSED) {
        $response = new Response($this->service->exportToFile($object_ids, $format));
        $response->headers->set('Content-Type', 'application/x-download');
        $response->headers->set(
                'Content-Disposition', 'attachment; filename="' . $this->service->getExportFileName($this->exportFilePrefix, $object_ids, $format) . '"'
        );
        return $response;
    }

    public function copyAction($object_id) {
        $result = $this->importService->copy($this->entityName, $this->securityTokenStorage->getToken()->getUser(), $object_id, $this->request->get("name"));
        $errors = array();
        foreach ($result as $r) {
            for ($i = 0; $i < count($r['errors']); $i++) {
                $errors[] = $r["source"]["class_name"] . "#" . $r["source"]["id"] . ": " . $this->translator->trans($r['errors'][$i]);
            }
        }
        if (count($errors) > 0) {
            $response = new Response(json_encode(array("result" => 1, "errors" => $errors)));
        } else {
            $response = new Response(json_encode(array("result" => 0, "object" => $result[0]['entity'], "object_id" => $result[0]['entity']->getId())));
        }
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

}
