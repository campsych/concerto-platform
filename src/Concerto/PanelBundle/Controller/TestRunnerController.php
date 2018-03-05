<?php

namespace Concerto\PanelBundle\Controller;

use Concerto\PanelBundle\Service\TestRunnerService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Templating\EngineInterface;
use Concerto\PanelBundle\Entity\TestSessionLog;
use Symfony\Component\HttpFoundation\RedirectResponse;

class TestRunnerController
{
    private $templating;
    private $testRunnerService;
    private $logger;
    private $settings;
    private $environment;
    private $session;

    public function __construct($environment, EngineInterface $templating, TestRunnerService $testRunnerService, LoggerInterface $logger, $settings, SessionInterface $session)
    {
        $this->templating = $templating;
        $this->testRunnerService = $testRunnerService;
        $this->logger = $logger;
        $this->settings = $settings;
        $this->environment = $environment;
        $this->session = $session;
    }

    /**
     * Returns start new test template.
     *
     * @Route("/test/{test_slug}/{params}", name="test_runner_start", defaults={"test_name":null,"params":"{}","debug":false})
     * @Route("/test_n/{test_name}/{params}", name="test_runner_start_name", defaults={"test_slug":null,"params":"{}"})
     * @param Request $request
     * @param string $test_slug
     * @param string $test_name
     * @param string $params
     * @param boolean $debug
     * @return Response
     */
    public function startNewTestAction(Request $request, $test_slug, $test_name = null, $params = "{}", $debug = false)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $test_slug, $test_name, $params");

        $locale = $request->get("locale");
        if($locale && $locale != $request->getLocale()) {
            $request->setLocale($locale);
            $request->setDefaultLocale($locale);
            $request->getSession()->set("_locale", $locale);
            return new RedirectResponse($request->getUri());
        }

        $params = json_decode($params, true);
        $keys = $request->query->keys();
        foreach ($keys as $k) {
            $params[$k] = $request->query->get($k);
        }
        $params = json_encode($params);

        $browser_valid = $this->testRunnerService->isBrowserValid($request->headers->get('User-Agent'));
        $panel_node = $this->testRunnerService->getPanelNodeById($request->get("node_id"));

        $response = $this->templating->renderResponse("ConcertoTestBundle::index.html.twig", array(
            "directory" => $panel_node["dir"] . ($this->environment === "dev" ? "app_dev.php/" : ""),
            "test_name" => $test_name,
            "test_slug" => $test_slug,
            "node_id" => $panel_node["id"],
            "params" => addcslashes($params, "'"),
            "keep_alive_interval" => $this->settings["keep_alive_interval_time"],
            "debug" => $debug,
            "browser_valid" => $browser_valid
        ));
        return $response;
    }

    /**
     * @Route("/admin/test/{test_slug}/debug/{params}", name="test_runner_start_debug", defaults={"params":"{}"})
     * @param Request $request
     * @param string $test_slug
     * @param string $params
     * @return Response
     */
    public function startNewDebugTestAction(Request $request, $test_slug, $params = "{}")
    {
        return $this->startNewTestAction($request, $test_slug, null, $params, true);
    }

    /**
     * @Route("/test/{test_slug}/session/start/{params}", name="test_runner_session_start", defaults={"test_name":null,"params":"{}","debug":false})
     * @Route("/test_n/{test_name}/session/start/{params}", name="test_runner_session_start_name", defaults={"test_slug":null,"params":"{}","debug":false})
     * @Method(methods={"POST"})
     * @param Request $request
     * @param $test_slug
     * @param $test_name
     * @param string $params
     * @param bool $debug
     * @return RedirectResponse|Response
     */
    public function startNewSessionAction(Request $request, $test_slug, $test_name = null, $params = "{}", $debug = false)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $test_slug, $test_name, $params, $debug");

        $panel_node = $this->testRunnerService->getPanelNodeById($request->get("node_id"));
        $response = null;
        if ($panel_node["local"] == "true") {
            $result = $this->testRunnerService->startNewSession(
                $test_slug,
                $test_name,
                $request->get("node_id"),
                $params,
                $request->getClientIp(),
                $request->server->get('HTTP_USER_AGENT'),
                $debug
            );
            $response = new Response($result);
            $response->headers->set('Content-Type', 'application/json');
            $response->headers->set('Access-Control-Allow-Origin', '*');
        } else {
            if ($debug) {
                return new Response("", 403);
            }

            if ($test_name !== null) {
                $url = $panel_node["protocol"] . "://" . $panel_node["web_host"] . ":" . $panel_node["web_port"] . $panel_node["dir"] . ($this->environment == "prod" ? "" : "app_dev.php/") . "test_n/$test_name/session/start/" . urlencode($params);
            } else {
                $url = $panel_node["protocol"] . "://" . $panel_node["web_host"] . ":" . $panel_node["web_port"] . $panel_node["dir"] . ($this->environment == "prod" ? "" : "app_dev.php/") . "test/$test_slug/session/start/" . urlencode($params);
            }
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - redirecting to URL : " . $url);
            $response = new RedirectResponse($url, 307);
        }
        $this->session->set("templateStartTime", microtime(true));
        return $response;
    }

    /**
     * @Route("/admin/test/{test_slug}/session/start/debug/{params}", name="test_runner_session_start_debug", defaults={"params":"{}"})
     * @Method(methods={"POST"})
     * @param Request $request
     * @param string $test_slug
     * @param string $params
     * @return RedirectResponse|Response
     */
    public function startNewDebugSessionAction(Request $request, $test_slug, $params = "{}")
    {
        return $this->startNewSessionAction($request, $test_slug, null, $params, true);
    }

    /**
     * @Route("/test/session/{session_hash}/submit", name="test_runner_session_submit")
     * @Method(methods={"POST"})
     * @param Request $request
     * @param string $session_hash
     * @return RedirectResponse|Response
     */
    public function submitToSessionAction(Request $request, $session_hash)
    {
        $time = microtime(true);

        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash");

        $panel_node = $this->testRunnerService->getPanelNodeById($request->get("node_id"));
        $response = null;
        if ($panel_node["local"] == "true") {
            $result = $this->testRunnerService->submitToSession(
                $session_hash,
                $request->get("node_id"),
                $request->get("values"),
                $request->getClientIp(),
                $request->server->get('HTTP_USER_AGENT'),
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

    /**
     * @Route("/test/session/{session_hash}/worker", name="test_runner_worker")
     * @Method(methods={"POST"})
     * @param Request $request
     * @param string $session_hash
     * @return RedirectResponse|Response
     */
    public function backgroundWorkerAction(Request $request, $session_hash)
    {
        $time = microtime(true);

        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash");

        $panel_node = $this->testRunnerService->getPanelNodeById($request->get("node_id"));
        $response = null;
        if ($panel_node["local"] == "true") {
            $result = $this->testRunnerService->backgroundWorker(
                $session_hash,
                $request->get("node_id"),
                $request->get("values"),
                $request->getClientIp(),
                $request->server->get('HTTP_USER_AGENT'),
                $time
            );
            $response = new Response($result);
            $response->headers->set('Content-Type', 'application/json');
            $response->headers->set('Access-Control-Allow-Origin', '*');
        } else {
            $url = $panel_node["protocol"] . "://" . $panel_node["web_host"] . ":" . $panel_node["web_port"] . $panel_node["dir"] . ($this->environment == "prod" ? "" : "app_dev.php/") . "test/session/$session_hash/worker";
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - redirecting to URL : " . $url);
            $response = new RedirectResponse($url, 307);
        }
        return $response;
    }

    /**
     * @Route("/test/session/{session_hash}/kill", name="test_runner_session_kill")
     * @Method(methods={"POST"})
     * @param Request $request
     * @param $session_hash
     * @return RedirectResponse|Response
     */
    public function killSessionAction(Request $request, $session_hash)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash");

        $panel_node = $this->testRunnerService->getPanelNodeById($request->get("node_id"));
        $response = null;
        if ($panel_node["local"] == "true") {
            $result = $this->testRunnerService->killSession(
                $session_hash, $request->get("node_id"), $request->getClientIp()
            );
            $response = new Response($result);
            $response->headers->set('Content-Type', 'application/json');
            $response->headers->set('Access-Control-Allow-Origin', '*');
        } else {
            $url = $panel_node["protocol"] . "://" . $panel_node["web_host"] . ":" . $panel_node["web_port"] . $panel_node["dir"] . ($this->environment == "prod" ? "" : "app_dev.php/") . "test/session/$session_hash/kill";
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - redirecting to URL : " . $url);
            $response = new RedirectResponse($url, 307);
        }
        return $response;
    }

    /**
     * @Route("/test/session/{session_hash}/keepalive", name="test_runner_session_keepalive")
     * @Method(methods={"POST"})
     * @param Request $request
     * @param string $session_hash
     * @return RedirectResponse|Response
     */
    public function keepAliveSessionAction(Request $request, $session_hash)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash");

        $panel_node = $this->testRunnerService->getPanelNodeById($request->get("node_id"));
        $response = null;
        if ($panel_node["local"] == "true") {
            $result = $this->testRunnerService->keepAliveSession(
                $session_hash, $request->get("node_id"), $request->getClientIp()
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

    /**
     * @Route("/test/session/{session_hash}/results", name="test_runner_session_results")
     * @Method(methods={"POST"})
     * @param Request $request
     * @param string $session_hash
     * @return RedirectResponse|Response
     */
    public function resultsFromSessionAction(Request $request, $session_hash)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash");

        $panel_node = $this->testRunnerService->getPanelNodeById($request->get("node_id"));
        $response = null;
        if ($panel_node["local"] == "true") {
            $result = $this->testRunnerService->resultsFromSession(
                $session_hash, $request->get("node_id")
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

    /**
     * @Route("/test/session/{session_hash}/upload", name="test_runner_upload_file")
     * @Method(methods={"POST","OPTIONS"})
     * @param Request $request
     * @param string $session_hash
     * @return RedirectResponse|Response
     */
    public function uploadFileAction(Request $request, $session_hash)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash");

        $panel_node = $this->testRunnerService->getPanelNodeById($request->get("node_id"));
        $response = null;
        if ($panel_node["local"] == "true") {
            $test_node = $this->testRunnerService->loadBalancerService->getTestNodeBySession($session_hash);
            if ($test_node["local"] == "true") {
                $result = $this->testRunnerService->uploadFile(
                    $session_hash,
                    $request->files,
                    $request->get("name")
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

    /**
     * @Route("/test/session/{session_hash}/log", name="test_runner_log_error")
     * @Method(methods={"POST"})
     * @param Request $request
     * @param string $session_hash
     * @return RedirectResponse|Response
     */
    public function logErrorAction(Request $request, $session_hash)
    {
        $panel_node = $this->testRunnerService->getPanelNodeById($request->get("node_id"));
        $response = null;
        if ($panel_node["local"] == "true") {
            $result = $this->testRunnerService->logError(
                $session_hash, //
                $request->get("node_id"), //
                $request->get("error"), //
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
