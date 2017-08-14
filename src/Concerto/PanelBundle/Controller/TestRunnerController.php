<?php

namespace Concerto\PanelBundle\Controller;

use Concerto\PanelBundle\Service\TestRunnerService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\Templating\EngineInterface;
use Concerto\PanelBundle\Entity\TestSessionLog;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;

class TestRunnerController
{
    private $templating;
    private $testRunnerService;
    private $request;
    private $logger;
    private $settings;
    private $environment;
    private $session;

    public function __construct($environment, EngineInterface $templating, TestRunnerService $testRunnerService, Request $request, LoggerInterface $logger, $settings, Session $session)
    {
        $this->templating = $templating;
        $this->testRunnerService = $testRunnerService;
        $this->request = $request;
        $this->logger = $logger;
        $this->settings = $settings;
        $this->environment = $environment;
        $this->session = $session;
    }

    /**
     * Returns start new test template.
     *
     * @param string $test_slug
     * @param json encoded string $params
     * @param boolean $debug
     * @return Response
     */
    public function startNewTestAction($test_slug, $params = "{}", $debug = false)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $test_slug, $params");

        $params = json_decode($params, true);
        $keys = $this->request->query->keys();
        foreach ($keys as $k) {
            $params[$k] = $this->request->query->get($k);
        }
        $params = json_encode($params);

        $browser_valid = $this->testRunnerService->isBrowserValid($this->request->headers->get('User-Agent'));
        $panel_node = $this->testRunnerService->getPanelNodeById($this->request->get("node_id"));

        $response = $this->templating->renderResponse("ConcertoTestBundle::index.html.twig", array(
            "directory" => $panel_node["dir"] . ($this->environment === "dev" ? "app_dev.php/" : ""),
            "test_slug" => $test_slug,
            "node_id" => $panel_node["id"],
            "params" => addcslashes($params, "'"),
            "keep_alive_interval" => $this->settings["keep_alive_interval_time"],
            "debug" => $debug,
            "browser_valid" => $browser_valid
        ));
        return $response;
    }

    public function startNewDebugTestAction($test_slug, $params = "{}")
    {
        return $this->startNewTestAction($test_slug, $params, true);
    }

    public function startNewSessionAction($test_slug, $params = "{}", $debug = false)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $test_slug, $params, $debug");

        $panel_node = $this->testRunnerService->getPanelNodeById($this->request->get("node_id"));
        $response = null;
        if ($panel_node["local"] == "true") {
            $result = $this->testRunnerService->startNewSession(
                $test_slug, //
                $this->request->get("node_id"), //
                $params, //
                $this->request->getClientIp(), //
                $this->request->server->get('HTTP_USER_AGENT'), //
                $debug //
            );
            $response = new Response($result);
            $response->headers->set('Content-Type', 'application/json');
            $response->headers->set('Access-Control-Allow-Origin', '*');
        } else {
            if ($debug) {
                //$url = $panel_node["protocol"] . "://" . $panel_node["web_host"] . ":" . $panel_node["web_port"] . $panel_node["dir"] . ($this->environment == "prod" ? "" : "app_dev.php/") . "admin/test/$test_slug/session/start/debug/" . urlencode($params);
                return new Response("", 403);
            }

            $url = $panel_node["protocol"] . "://" . $panel_node["web_host"] . ":" . $panel_node["web_port"] . $panel_node["dir"] . ($this->environment == "prod" ? "" : "app_dev.php/") . "test/$test_slug/session/start/" . urlencode($params);
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - redirecting to URL : " . $url);
            $response = new RedirectResponse($url, 307);
        }
        $this->session->set("templateStartTime", microtime(true));
        return $response;
    }

    public function startNewDebugSessionAction($test_slug, $params = "{}")
    {
        return $this->startNewSessionAction($test_slug, $params, true);
    }

    public function submitToSessionAction($session_hash)
    {
        $time = microtime(true);

        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash");

        $panel_node = $this->testRunnerService->getPanelNodeById($this->request->get("node_id"));
        $response = null;
        if ($panel_node["local"] == "true") {
            $result = $this->testRunnerService->submitToSession(
                $session_hash,
                $this->request->get("node_id"),
                $this->request->get("values"),
                $this->request->getClientIp(),
                $this->request->server->get('HTTP_USER_AGENT'),
                $time
            );
            $response = new Response($result);
            $response->headers->set('Content-Type', 'application/json');
            $response->headers->set('Access-Control-Allow-Origin', '*');
        } else {
            $url = $panel_node["protocol"] . "://" . $panel_node["web_host"] . ":" . $panel_node["web_port"] . $panel_node["dir"] . ($this->environment == "prod" ? "" : "app_dev.php/") . "test/session/$session_hash/submit";
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - redirecting to URL : " . $url);
            $response = new RedirectResponse($url, 307);
        }
        $this->session->set("templateStartTime", microtime(true));
        return $response;
    }

    public function keepAliveSessionAction($session_hash)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash");

        $panel_node = $this->testRunnerService->getPanelNodeById($this->request->get("node_id"));
        $response = null;
        if ($panel_node["local"] == "true") {
            $result = $this->testRunnerService->keepAliveSession(
                $session_hash, $this->request->get("node_id"), $this->request->getClientIp()
            );
            $response = new Response($result);
            $response->headers->set('Content-Type', 'application/json');
            $response->headers->set('Access-Control-Allow-Origin', '*');
        } else {
            $url = $panel_node["protocol"] . "://" . $panel_node["web_host"] . ":" . $panel_node["web_port"] . $panel_node["dir"] . ($this->environment == "prod" ? "" : "app_dev.php/") . "test/session/$session_hash/keepalive";
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - redirecting to URL : " . $url);
            $response = new RedirectResponse($url, 307);
        }
        return $response;
    }

    public function resultsFromSessionAction($session_hash)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash");

        $panel_node = $this->testRunnerService->getPanelNodeById($this->request->get("node_id"));
        $response = null;
        if ($panel_node["local"] == "true") {
            $result = $this->testRunnerService->resultsFromSession(
                $session_hash, $this->request->get("node_id")
            );
            $response = new Response($result);
            $response->headers->set('Content-Type', 'application/json');
            $response->headers->set('Access-Control-Allow-Origin', '*');
        } else {
            $url = $panel_node["protocol"] . "://" . $panel_node["web_host"] . ":" . $panel_node["web_port"] . $panel_node["dir"] . ($this->environment == "prod" ? "" : "app_dev.php/") . "test/session/$session_hash/results";
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - redirecting to URL : " . $url);
            $response = new RedirectResponse($url, 307);
        }
        return $response;
    }

    public function uploadFileAction($session_hash)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash");

        $panel_node = $this->testRunnerService->getPanelNodeById($this->request->get("node_id"));
        $response = null;
        if ($panel_node["local"] == "true") {
            $test_node = $this->testRunnerService->loadBalancerService->getTestNodeBySession($session_hash);
            if ($test_node["local"] == "true") {
                $result = $this->testRunnerService->uploadFile(
                    $session_hash, //
                    $this->request->files, //
                    $this->request->get("name")
                );
                $response = new Response($result);
                $response->headers->set('Content-Type', 'application/json');
                $response->headers->set('Access-Control-Allow-Origin', '*');
            } else {
                $url = $test_node["protocol"] . "://" . $test_node["web_host"] . ":" . $test_node["web_port"] . $test_node["dir"] . ($this->environment == "prod" ? "" : "app_dev.php/") . "test/r/session/$session_hash/upload";
                $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - redirecting to URL : " . $url);
                $response = new RedirectResponse($url, 307);
            }
        } else {
            $url = $panel_node["protocol"] . "://" . $panel_node["web_host"] . ":" . $panel_node["web_port"] . $panel_node["dir"] . ($this->environment == "prod" ? "" : "app_dev.php/") . "test/session/$session_hash/upload";
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - redirecting to URL : " . $url);
            $response = new RedirectResponse($url, 307);
        }
        return $response;
    }

    public function logErrorAction($session_hash)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash");

        $panel_node = $this->testRunnerService->getPanelNodeById($this->request->get("node_id"));
        $response = null;
        if ($panel_node["local"] == "true") {
            $result = $this->testRunnerService->logError(
                $session_hash, //
                $this->request->get("node_id"), //
                $this->request->get("error"), //
                TestSessionLog::TYPE_JS
            );
            $response = new Response($result);
            $response->headers->set('Content-Type', 'application/json');
            $response->headers->set('Access-Control-Allow-Origin', '*');
        } else {
            $url = $panel_node["protocol"] . "://" . $panel_node["web_host"] . ":" . $panel_node["web_port"] . $panel_node["dir"] . ($this->environment == "prod" ? "" : "app_dev.php/") . "test/session/$session_hash/log";
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - redirecting to URL : " . $url);
            $response = new RedirectResponse($url, 307);
        }
        return $response;
    }

}
