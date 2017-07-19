<?php

namespace Concerto\PanelBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Concerto\PanelBundle\Service\PanelService;
use Concerto\PanelBundle\Service\FileService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\AssetsHelper;

/**
 * @Security("has_role('ROLE_FILE') or has_role('ROLE_SUPER_ADMIN')")
 */
class FileBrowserController {

    private $templating;
    private $service;
    private $request;
    private $fileService;
    private $assetHelper;

    public function __construct(EngineInterface $templating, PanelService $service, FileService $fileService, Request $request, AssetsHelper $assetHelper) {
        $this->templating = $templating;
        $this->service = $service;
        $this->fileService = $fileService;
        $this->request = $request;
        $this->assetHelper = $assetHelper;
    }

    /**
     * 
     * @return Response
     */
    public function fileUploadAction() {
        $response = new Response(json_encode(array("result" => 0)));
        foreach ($this->request->files as $file) {
            if (!$this->fileService->moveUploadedFile($file->getRealPath(), $file->getClientOriginalName(), $message)) {
                $response = new Response(json_encode(array("result" => 1, "error" => $message)));
                break;
            }
        }
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @return Response
     */
    public function fileListAction() {
        $files = $this->fileService->listUploadedFiles($this->assetHelper->getUrl("bundles/concertopanel/files/"));
        // if there are any errors <=> files service returned false, we return error status 1
        $response = new Response(json_encode((false === $files) ? array("result" => 1) : array("result" => 0, "files" => $files)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @return Response
     */
    public function fileDeleteAction($filename) {
        $response = new Response(json_encode(array("result" => (int) (!$this->fileService->deleteUploadedFile($filename)))));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @return Response
     */
    public function fileBrowserAction() {
        $cke_callback = $this->request->get('CKEditorFuncNum');

        return $this->templating->renderResponse(
                        'ConcertoPanelBundle:FileBrowser:file_browser.html.twig', empty($cke_callback) ? array() : array('cke_callback' => $cke_callback)
        );
    }

}
