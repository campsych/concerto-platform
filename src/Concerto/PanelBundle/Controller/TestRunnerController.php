<?php

namespace Concerto\PanelBundle\Controller;

use Concerto\PanelBundle\Service\TestRunnerService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\Templating\EngineInterface;
use Concerto\PanelBundle\Entity\TestSessionLog;
use Symfony\Component\HttpFoundation\RedirectResponse;

class TestRunnerController {

    private $templating;
    private $testRunnerService;
    private $request;
    private $logger;
    private $settings;
    private $environment;

    public function __construct($environment, EngineInterface $templating, TestRunnerService $testRunnerService, Request $request, LoggerInterface $logger, $settings) {
        $this->templating = $templating;
        $this->testRunnerService = $testRunnerService;
        $this->request = $request;
        $this->logger = $logger;
        $this->settings = $settings;
        $this->environment = $environment;
    }

    /**
     * Returns start new test template with session resuming capabailities.
     * 
     * @param string $test_slug
     * @param json encoded string $params
     * @param boolean $debug
     * @return Response
     */
    public function startNewTestAction($test_slug, $params = "{}", $debug = false) {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $test_slug, $params");

        $params = json_decode($params, true);
        $keys = $this->request->query->keys();
        foreach ($keys as $k) {
            $params[$k] = $this->request->query->get($k);
        }
        $params = json_encode($params);

        $browser_valid = $this->testRunnerService->isBrowserValid($this->request->headers->get('User-Agent'));
        $node = $this->testRunnerService->getPanelNodeById($this->request->get("node_id"));
        return $this->templating->renderResponse("ConcertoTestBundle::index.html.twig", array(
                    "directory" => $node["dir"] . ($this->environment === "dev" ? "app_dev.php/" : ""),
                    "test_slug" => $test_slug,
                    "node_id" => $node["id"],
                    "params" => addcslashes($params, "'"),
                    "keep_alive_interval" => $this->settings["keep_alive_interval_time"],
                    "debug" => $debug,
                    "browser_valid" => $browser_valid
        ));
    }

    public function startNewDebugTestAction($test_slug, $params = "{}") {
        return $this->startNewTestAction($test_slug, $params, true);
    }

    public function startNewSessionAction($test_slug, $params = "{}") {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $test_slug, $params");

        $panel_node = $this->testRunnerService->getPanelNodeById($this->request->get("node_id"));
        $response = null;
        if ($panel_node["local"]) {
            $result = $this->testRunnerService->startNewSession(
                    $test_slug, $this->request->get("node_id"), $params, $this->request->getClientIp(), $this->request->server->get('HTTP_USER_AGENT')
            );
            $response = new Response($result);
            $response->headers->set('Content-Type', 'application/json');
        } else {
            $url = $panel_node["protocol"] . "://" . $panel_node["host"] . $panel_node["dir"] . ($this->environment == "prod" ? "" : "app_dev.php/") . "TestSession/Test/$test_slug/start/" . urlencode($params);
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - redirecting to URL : " . $url);
            $response = new RedirectResponse($url, 307);
        }
        return $response;
    }

    public function submitToSessionAction($session_hash) {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash");

        $panel_node = $this->testRunnerService->getPanelNodeById($this->request->get("node_id"));
        $response = null;
        if ($panel_node["local"]) {
            $result = $this->testRunnerService->submitToSession(
                    $session_hash, $this->request->get("node_id"), $this->request->get("values"), $this->request->getClientIp(), $this->request->server->get('HTTP_USER_AGENT')
            );
            $response = new Response($result);
            $response->headers->set('Content-Type', 'application/json');
        } else {
            $url = $panel_node["protocol"] . "://" . $panel_node["host"] . $panel_node["dir"] . ($this->environment == "prod" ? "" : "app_dev.php/") . "TestSession/$session_hash/submit";
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - redirecting to URL : " . $url);
            $response = new RedirectResponse($url, 307);
        }
        return $response;
    }

    public function keepAliveSessionAction($session_hash) {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash");

        $panel_node = $this->testRunnerService->getPanelNodeById($this->request->get("node_id"));
        $response = null;
        if ($panel_node["local"]) {
            $result = $this->testRunnerService->keepAliveSession(
                    $session_hash, $this->request->get("node_id"), $this->request->getClientIp()
            );
            $response = new Response($result);
            $response->headers->set('Content-Type', 'application/json');
        } else {
            $url = $panel_node["protocol"] . "://" . $panel_node["host"] . $panel_node["dir"] . ($this->environment == "prod" ? "" : "app_dev.php/") . "TestSession/$session_hash/keepalive";
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - redirecting to URL : " . $url);
            $response = new RedirectResponse($url, 307);
        }
        return $response;
    }

    public function resumeSessionAction($session_hash) {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash");

        $panel_node = $this->testRunnerService->getPanelNodeById($this->request->get("node_id"));
        $response = null;
        if ($panel_node["local"]) {
            $result = $this->testRunnerService->resumeSession(
                    $session_hash, $this->request->get("node_id")
            );
            $response = new Response($result);
            $response->headers->set('Content-Type', 'application/json');
        } else {
            $url = $panel_node["protocol"] . "://" . $panel_node["host"] . $panel_node["dir"] . ($this->environment == "prod" ? "" : "app_dev.php/") . "TestSession/$session_hash/resume";
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - redirecting to URL : " . $url);
            $response = new RedirectResponse($url, 307);
        }
        return $response;
    }

    public function resultsFromSessionAction($session_hash) {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash");

        $panel_node = $this->testRunnerService->getPanelNodeById($this->request->get("node_id"));
        $response = null;
        if ($panel_node["local"]) {
            $result = $this->testRunnerService->resultsFromSession(
                    $session_hash, $this->request->get("node_id")
            );
            $response = new Response($result);
            $response->headers->set('Content-Type', 'application/json');
        } else {
            $url = $panel_node["protocol"] . "://" . $panel_node["host"] . $panel_node["dir"] . ($this->environment == "prod" ? "" : "app_dev.php/") . "TestSession/$session_hash/results";
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - redirecting to URL : " . $url);
            $response = new RedirectResponse($url, 307);
        }
        return $response;
    }

    public function uploadFileAction($session_hash) {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash");
        $result = $this->testRunnerService->uploadFile(
                $session_hash, //
                $this->request->get("node_id"), //
                $this->request->files, //
                $this->request->get("name")
        );
        $response = new Response($result);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function logErrorAction($session_hash) {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash");

        $panel_node = $this->testRunnerService->getPanelNodeById($this->request->get("node_id"));
        $response = null;
        if ($panel_node["local"]) {
            $result = $this->testRunnerService->logError(
                    $session_hash, //
                    $this->request->get("node_id"), //
                    $this->request->get("error"), //
                    TestSessionLog::TYPE_JS
            );
            $response = new Response($result);
            $response->headers->set('Content-Type', 'application/json');
        } else {
            $url = $panel_node["protocol"] . "://" . $panel_node["host"] . $panel_node["dir"] . ($this->environment == "prod" ? "" : "app_dev.php/") . "TestSession/$session_hash/log";
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - redirecting to URL : " . $url);
            $response = new RedirectResponse($url, 307);
        }
        return $response;
    }

}
