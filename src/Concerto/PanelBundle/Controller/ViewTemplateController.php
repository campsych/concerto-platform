<?php

namespace Concerto\PanelBundle\Controller;

use Concerto\PanelBundle\Service\FileService;
use Concerto\PanelBundle\Service\ViewTemplateService;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;
use Concerto\PanelBundle\Service\ImportService;
use Concerto\PanelBundle\Service\ExportService;
use Concerto\PanelBundle\Service\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;

class ViewTemplateController extends AExportableTabController
{

    const ENTITY_NAME = "ViewTemplate";
    const EXPORT_FILE_PREFIX = "ViewTemplate";

    private $userService;

    public function __construct($environment, EngineInterface $templating, ViewTemplateService $service, TranslatorInterface $translator, ImportService $importService, ExportService $exportService, UserService $userService, FileService $fileService)
    {
        parent::__construct($environment, $templating, $service, $translator, $importService, $exportService, $fileService);

        $this->entityName = self::ENTITY_NAME;
        $this->exportFilePrefix = self::EXPORT_FILE_PREFIX;

        $this->userService = $userService;
    }

    /**
     * @Route("/admin/ViewTemplate/fetch/{object_id}/{format}", name="ViewTemplate_object", defaults={"format":"json"})
     * @Security("has_role('ROLE_TEMPLATE') or has_role('ROLE_SUPER_ADMIN')")
     * @param $object_id
     * @param string $format
     * @return Response
     */
    public function objectAction($object_id, $format = "json")
    {
        return parent::objectAction($object_id, $format);
    }

    /**
     * @Route("/admin/ViewTemplate/collection/{format}", name="ViewTemplate_collection", defaults={"format":"json"})
     * @Security("has_role('ROLE_TEMPLATE') or has_role('ROLE_SUPER_ADMIN')")
     * @param string $format
     * @return Response
     */
    public function collectionAction($format = "json")
    {
        return parent::collectionAction($format);
    }

    /**
     * @Route("/admin/ViewTemplate/{object_id}/toggleLock", name="ViewTemplate_toggleLock")
     * @Security("has_role('ROLE_TEMPLATE') or has_role('ROLE_SUPER_ADMIN')")
     * @param Request $request
     * @param $object_id
     * @return Response
     */
    public function toggleLock(Request $request, $object_id)
    {
        return parent::toggleLock($request, $object_id);
    }

    /**
     * @Route("/admin/ViewTemplate/form/{action}", name="ViewTemplate_form", defaults={"action":"edit"})
     * @Security("has_role('ROLE_TEMPLATE') or has_role('ROLE_SUPER_ADMIN')")
     * @param string $action
     * @param array $params
     * @return Response
     */
    public function formAction($action = "edit", $params = array())
    {
        return parent::formAction($action, $params);
    }

    /**
     * @Route("/admin/ViewTemplate/{object_id}/save", name="ViewTemplate_save", methods={"POST"})
     * @Security("has_role('ROLE_TEMPLATE') or has_role('ROLE_SUPER_ADMIN')")
     * @param Request $request
     * @param $object_id
     * @return Response
     */
    public function saveAction(Request $request, $object_id)
    {
        if (!$this->service->canBeModified($object_id, $request->get("objectTimestamp"), $errorMessages)) {
            $response = new Response(json_encode(array("result" => 1, "errors" => $this->trans($errorMessages))));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $result = $this->service->save(
            $object_id,
            $request->get("name"),
            $request->get("description"),
            $request->get("accessibility"),
            $request->get("archived") === "1",
            $this->userService->get($request->get("owner")),
            $request->get("groups"),
            $request->get("html"),
            $request->get("head"),
            $request->get("css"),
            $request->get("js"));
        return $this->getSaveResponse($result);
    }

    /**
     * @Route("/admin/ViewTemplate/{object_id}/copy", name="ViewTemplate_copy")
     * @Security("has_role('ROLE_TEMPLATE') or has_role('ROLE_SUPER_ADMIN')")
     * @param Request $request
     * @param $object_id
     * @return Response
     */
    public function copyAction(Request $request, $object_id)
    {
        return parent::copyAction($request, $object_id);
    }

    /**
     * @Route("/admin/ViewTemplate/{object_ids}/delete", name="ViewTemplate_delete", methods={"POST"})
     * @Security("has_role('ROLE_TEMPLATE') or has_role('ROLE_SUPER_ADMIN')")
     * @param Request $request
     * @param string $object_ids
     * @return Response
     */
    public function deleteAction(Request $request, $object_ids)
    {
        return parent::deleteAction($request, $object_ids);
    }

    /**
     * @Route("/admin/ViewTemplate/{instructions}/export/{format}", name="ViewTemplate_export", defaults={"format":"yml"})
     * @Security("has_role('ROLE_TEMPLATE') or has_role('ROLE_SUPER_ADMIN')")
     * @param string $instructions
     * @param string $format
     * @return Response
     */
    public function exportAction($instructions, $format = "yml")
    {
        return parent::exportAction($instructions, $format);
    }

    /**
     * @Route("/admin/ViewTemplate/{object_ids}/instructions/export", name="ViewTemplate_export_instructions")
     * @Security("has_role('ROLE_TEMPLATE') or has_role('ROLE_SUPER_ADMIN')")
     * @param $object_ids
     * @return Response
     */
    public function exportInstructionsAction($object_ids)
    {
        return parent::exportInstructionsAction($object_ids);
    }

    /**
     * @Route("/admin/ViewTemplate/import", name="ViewTemplate_import", methods={"POST"})
     * @Security("has_role('ROLE_TEMPLATE') or has_role('ROLE_SUPER_ADMIN')")
     * @param Request $request
     * @return Response
     */
    public function scheduleImportAction(Request $request)
    {
        return parent::scheduleImportAction($request);
    }

    /**
     * @Route("/admin/ViewTemplate/import/status", name="ViewTemplate_pre_import_status")
     * @Security("has_role('ROLE_TEMPLATE') or has_role('ROLE_SUPER_ADMIN')")
     * @param Request $request
     * @return Response
     */
    public function preImportStatusAction(Request $request)
    {
        return parent::preImportStatusAction($request);
    }

    /**
     * @Route("/ViewTemplate/{id}/content", name="ViewTemplate_content", methods={"GET"})
     * @param Request $request
     * @param string $id
     * @return Response
     */
    public function contentAction(Request $request, $id)
    {
        $showHtml = true;
        $showCss = true;
        $showJs = true;

        $htmlOverride = $request->get("html");
        if ($htmlOverride !== null) $showHtml = $htmlOverride == 1;
        $cssOverride = $request->get("css");
        if ($cssOverride !== null) $showCss = $cssOverride == 1;
        $jsOverride = $request->get("js");
        if ($jsOverride !== null) $showJs = $jsOverride == 1;

        $content = $this->service->getContent($id, true, $showHtml, $showCss, $showJs);
        if ($content === false) {
            return new Response('', 404);
        }
        return new Response($content, 200);;
    }

    /**
     * @Route("/ViewTemplate/{id}/css", name="ViewTemplate_css", methods={"GET"})
     * @param Request $request
     * @param string $id
     * @return Response
     */
    public function cssAction(Request $request, $id)
    {
        $content = $this->service->getContent($id, false, false, true, false);
        if ($content === false) {
            return new Response('', 404);
        }
        $response = new Response($content, 200);
        $response->headers->set('Content-Type', 'text/css');
        return $response;
    }

    /**
     * @Route("/ViewTemplate/{id}/js", name="ViewTemplate_js", methods={"GET"})
     * @param Request $request
     * @param string $id
     * @return Response
     */
    public function jsAction(Request $request, $id)
    {
        $content = $this->service->getContent($id, false, false, false, true);
        if ($content === false) {
            return new Response('', 404);
        }
        $response = new Response($content, 200);
        $response->headers->set('Content-Type', 'text/javascript');
        return $response;
    }

    /**
     * @Route("/ViewTemplate/{id}/html", name="ViewTemplate_html", methods={"GET"})
     * @param Request $request
     * @param string $id
     * @return Response
     */
    public function htmlAction(Request $request, $id)
    {
        $content = $this->service->getContent($id, false, true, false, false);
        if ($content === false) {
            return new Response('', 404);
        }
        return new Response($content, 200);;
    }
}
