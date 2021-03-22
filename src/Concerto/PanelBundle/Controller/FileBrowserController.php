<?php

namespace Concerto\PanelBundle\Controller;

use Concerto\PanelBundle\Service\FileService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/admin")
 * @Security("has_role('ROLE_FILE') or has_role('ROLE_SUPER_ADMIN')")
 */
class FileBrowserController
{

    private $templating;
    private $fileService;
    private $translator;

    public function __construct(EngineInterface $templating, FileService $fileService, TranslatorInterface $translator)
    {
        $this->templating = $templating;
        $this->fileService = $fileService;
        $this->translator = $translator;
    }

    /**
     * @Route("/file/list", name="FileBrowser_list", methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function listAction(Request $request)
    {
        $path = $request->get("path");
        $result = $this->fileService->listFiles($path);
        return $this->successPostResponse($result);
    }

    /**
     * @Route("/file/upload", name="FileBrowser_upload", methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function uploadAction(Request $request)
    {
        $dir = $request->get("dir");
        $destination = $request->get("destination");
        if ($dir === null) $dir = FileService::DIR_PUBLIC;
        $result = $this->fileService->uploadFiles($dir, $destination, $request->files, $error);
        if ($result === true) return $this->successPostResponse();
        else return $this->errorResponse($error);
    }

    /**
     * @Route("/file/rename", name="FileBrowser_rename", methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function renameAction(Request $request)
    {
        $item = $request->get("item");
        $newItemPath = $request->get("newItemPath");
        $result = $this->fileService->renameFile($item, $newItemPath, $error);
        if ($result === true) return $this->successPostResponse();
        else return $this->errorResponse($error);
    }

    /**
     * @Route("/file/copy", name="FileBrowser_copy", methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function copyAction(Request $request)
    {
        $items = $request->get("items");
        $newPath = $request->get("newPath");
        $singleDstFileName = $request->get("singleFilename");
        $result = $this->fileService->copyFiles($items, $newPath, $singleDstFileName, $error);
        if ($result === true) return $this->successPostResponse();
        else return $this->errorResponse($error);
    }

    /**
     * @Route("/file/move", name="FileBrowser_move", methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function moveAction(Request $request)
    {
        $items = $request->get("items");
        $newPath = $request->get("newPath");
        $result = $this->fileService->moveFiles($items, $newPath, $error);
        if ($result === true) return $this->successPostResponse();
        else return $this->errorResponse($error);
    }

    /**
     * @Route("/file/delete", name="FileBrowser_delete", methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function deleteAction(Request $request)
    {
        $items = $request->get("items");
        $result = $this->fileService->deleteFiles($items, $error);
        if ($result === true) return $this->successPostResponse();
        else return $this->errorResponse($error);
    }

    /**
     * @Route("/file/edit", name="FileBrowser_edit", methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function editAction(Request $request)
    {
        $item = $request->get("item");
        $content = $request->get("content");
        $result = $this->fileService->editFile($item, $content, $error);
        if ($result === true) return $this->successPostResponse();
        else return $this->errorResponse($error);
    }

    /**
     * @Route("/file/content", name="FileBrowser_content", methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function getContentAction(Request $request)
    {
        $item = $request->get("item");
        $result = $this->fileService->getFileContent($item, $error);
        if ($result === false) return $this->errorResponse($error);
        else return $this->successPostResponse($result);
    }

    /**
     * @Route("/file/create_directory", name="FileBrowser_create_directory", methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function createDirectoryAction(Request $request)
    {
        $newPath = $request->get("newPath");
        $result = $this->fileService->createDirectory($newPath, $error);
        if ($result === true) return $this->successPostResponse();
        else return $this->errorResponse($error);
    }

    /**
     * @Route("/file/download", name="FileBrowser_download", methods={"GET"})
     * @param Request $request
     * @return Response
     */
    public function downloadAction(Request $request)
    {
        $path = $request->get("path");
        $fileName = $this->fileService->canonicalizePath(basename($path));
        $path = realpath($this->fileService->getPublicUploadDirectory()) . "/" . $this->fileService->canonicalizePath($path);
        if (!file_exists($path)) {
            return $this->errorResponse("file_not_found");
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $path);
        finfo_close($finfo);

        $response = new Response(file_get_contents($path));
        $response->headers->set("Content-Disposition", "attachment; filename=\"$fileName\"");
        $response->headers->set("Cache-Control", "must-revalidate, post-check=0, pre-check=0");
        $response->headers->set("Content-Type", $mime_type);
        $response->headers->set("Pragma", "public");
        $response->headers->set("Content-Length", filesize($path));
        return $response;
    }

    /**
     * @Route("/file/download_multiple", name="FileBrowser_download_multiple", methods={"GET"})
     * @param Request $request
     * @return Response
     */
    public function downloadMultipleAction(Request $request)
    {
        $toFilename = $request->get("toFilename");
        $items = json_decode($request->get("items"), true);

        $zipResult = $this->fileService->createTempArchive($items, $archivePath, $error);
        if ($zipResult !== true) {
            return $this->errorResponse($error);
        }

        $response = new Response(file_get_contents($archivePath));
        $response->headers->set("Content-Disposition", "attachment; filename=\"$toFilename\"");
        $response->headers->set("Cache-Control", "must-revalidate, post-check=0, pre-check=0");
        $response->headers->set("Content-Type", "application/zip");
        $response->headers->set("Pragma", "public");
        $response->headers->set("Content-Length", filesize($archivePath));
        unlink($archivePath);
        return $response;
    }

    /**
     * @Route("/file/compress", name="FileBrowser_compress", methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function compressAction(Request $request)
    {
        $items = $request->get("items");
        $destination = $request->get("destination");
        $compressedFilename = $request->get("compressedFilename");
        $result = $this->fileService->compressFiles($items, $destination, $compressedFilename, $error);
        if ($result === true) return $this->successPostResponse();
        else return $this->errorResponse($error);
    }

    /**
     * @Route("/file/extract", name="FileBrowser_extract", methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function extractAction(Request $request)
    {
        $destination = $request->get("destination");
        $item = $request->get("item");
        $folderName = $request->get("folderName");
        $result = $this->fileService->extractFiles($destination, $item, $folderName, $error);
        if ($result === true) return $this->successPostResponse();
        else return $this->errorResponse($error);
    }

    /**
     * @Route("/file/permissions", name="FileBrowser_permissions", methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function permissionsAction(Request $request)
    {
        $items = $request->get("items");
        $permsCode = $request->get("permsCode");
        $recursive = $request->get("recursive");
        $result = $this->fileService->setPermissions($items, $permsCode, $recursive, $error);
        if ($result === true) return $this->successPostResponse();
        else return $this->errorResponse($error);
    }

    /**
     * @Route("/file/browser", name="FileBrowser_browser")
     * @param Request $request
     * @return Response
     */
    public function fileBrowserAction(Request $request)
    {
        return $this->templating->renderResponse('ConcertoPanelBundle:FileBrowser:file_browser.html.twig');
    }

    private function errorResponse($errorMessage, $status = 500)
    {
        $response = new Response(json_encode(array("result" => array(
            "success" => false,
            "error" => $this->translator->trans("errors.$errorMessage", array(), "FileBrowser")
        ))), $status);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    private function successPostResponse($result = null)
    {
        $response = new Response();
        if ($result !== null) {
            $response->setContent(json_encode(array("result" => $result)));
        } else {
            $response->setContent(json_encode(array("result" => array(
                "success" => true,
                "error" => null
            ))));
        }
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}
