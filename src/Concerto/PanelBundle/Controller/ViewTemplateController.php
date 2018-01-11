<?php

namespace Concerto\PanelBundle\Controller;

use Concerto\PanelBundle\Service\FileService;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Concerto\PanelBundle\Service\AExportableSectionService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Concerto\PanelBundle\Service\ImportService;
use Concerto\PanelBundle\Service\ExportService;
use Concerto\PanelBundle\Service\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Security("has_role('ROLE_TEMPLATE') or has_role('ROLE_SUPER_ADMIN')")
 */
class ViewTemplateController extends AExportableTabController {

    const ENTITY_NAME = "ViewTemplate";
    const EXPORT_FILE_PREFIX = "ViewTemplate_";

    private $userService;

    public function __construct($environment, EngineInterface $templating, AExportableSectionService $service, Request $request, TranslatorInterface $translator, TokenStorage $securityTokenStorage, ImportService $importService, ExportService $exportService, UserService $userService, FileService $fileService) {
        parent::__construct($environment, $templating, $service, $request, $translator, $securityTokenStorage, $importService, $exportService, $fileService);

        $this->entityName = self::ENTITY_NAME;
        $this->exportFilePrefix = self::EXPORT_FILE_PREFIX;

        $this->userService = $userService;
    }

    public function saveAction($object_id) {
        $result = $this->service->save(
                $this->securityTokenStorage->getToken()->getUser(), //
                $object_id, //
                $this->request->get("name"), //
                $this->request->get("description"), //
                $this->request->get("accessibility"), //
                $this->request->get("archived") === "1", //
                $this->userService->get($this->request->get("owner")), //
                $this->request->get("groups"), //
                $this->request->get("html"), //
                $this->request->get("head"), //
                $this->request->get("css"), //
                $this->request->get("js"));
        return $this->getSaveResponse($result);
    }

}
