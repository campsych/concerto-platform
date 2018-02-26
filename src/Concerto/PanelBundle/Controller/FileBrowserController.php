<?php

namespace Concerto\PanelBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Concerto\PanelBundle\Service\PanelService;
use Concerto\PanelBundle\Service\FileService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin")
 * @Security("has_role('ROLE_FILE') or has_role('ROLE_SUPER_ADMIN')")
 */
class FileBrowserController
{

    private $templating;
    private $service;
    private $fileService;
    private $assetHelper;

    public function __construct(EngineInterface $templating, PanelService $service, FileService $fileService, Packages $assetHelper)
    {
        $this->templating = $templating;
        $this->service = $service;
        $this->fileService = $fileService;
        $this->assetHelper = $assetHelper;
    }

    /**
     * @Route("/file/upload", name="FileBrowser_upload")
     * @Method(methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function fileUploadAction(Request $request)
    {
        $dir = $request->request->get("dir");
        if ($dir === null) $dir = FileService::DIR_PUBLIC;

        $response = new Response(json_encode(array("result" => 0)));
        foreach ($request->files as $file) {
            if (!$this->fileService->moveUploadedFile($file->getRealPath(), $dir, $file->getClientOriginalName(), $message)) {
                $response = new Response(json_encode(array("result" => 1, "error" => $message)));
                break;
            }
        }
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/file/list", name="FileBrowser_list")
     * @return Response
     */
    public function fileListAction()
    {
        $files = $this->fileService->listUploadedFiles($this->assetHelper->getUrl("bundles/concertopanel/files/"));
        // if there are any errors <=> files service returned false, we return error status 1
        $response = new Response(json_encode((false === $files) ? array("result" => 1) : array("result" => 0, "files" => $files)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/file/delete/{filename}", name="FileBrowser_delete")
     * @Method(methods={"POST"})
     * @param string $filename
     * @return Response
     */
    public function fileDeleteAction($filename)
    {
        $response = new Response(json_encode(array("result" => (int)(!$this->fileService->deleteUploadedFile($filename)))));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/file/browser", name="FileBrowser_browser")
     * @param Request $request
     * @return Response
     */
    public function fileBrowserAction(Request $request)
    {
        $cke_callback = $request->get('CKEditorFuncNum');

        return $this->templating->renderResponse(
            'ConcertoPanelBundle:FileBrowser:file_browser.html.twig', empty($cke_callback) ? array() : array('cke_callback' => $cke_callback)
        );
    }

}
