<?php

namespace Concerto\TestBundle\Controller;

use Concerto\TestBundle\Service\RRunnerService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Templating\EngineInterface;

class RRunnerController
{

    private $templating;
    private $rRunnerService;
    private $logger;

    public function __construct(EngineInterface $templating, RRunnerService $rRunnerService, LoggerInterface $logger)
    {
        $this->templating = $templating;
        $this->rRunnerService = $rRunnerService;
        $this->logger = $logger;
    }

    /**
     * @Route("/test/session/{session_hash}/start/{params}", name="r_runner_r_start", defaults={"params"="{}"} )
     * @Method(methods={"POST"})
     * @param Request $request
     * @param string $session_hash
     * @return Response
     */
    public function startRAction(Request $request, $session_hash)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash");

        $client_ip = $request->get("client_ip");
        $client_browser = $request->get("client_browser");
        $calling_node_ip = $request->getClientIp();
        return new Response($this->rRunnerService->startR(
            $request->get("panel_node_hash"),
            $request->get("panel_node_port"),
            $session_hash,
            $request->get("values"),
            $client_ip,
            $client_browser,
            $calling_node_ip,
            $request->get("debug")
        ));
    }

    /**
     * @Route("/test/r/session/{session_hash}/upload", name="r_runner_upload_file")
     * @Method(methods={"POST","OPTIONS"})
     * @param Request $request
     * @param string $session_hash
     * @return Response
     */
    public function uploadFileAction(Request $request, $session_hash)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash");

        $result = $this->rRunnerService->uploadFile(
            $session_hash,
            $request->getClientIp(),
            $request->files,
            $request->get("name")
        );
        $response = new Response($result);
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        return $response;
    }
}
