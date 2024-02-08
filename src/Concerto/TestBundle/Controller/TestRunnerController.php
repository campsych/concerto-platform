<?php

namespace Concerto\TestBundle\Controller;

use Concerto\TestBundle\Service\ASessionRunnerService;
use Concerto\TestBundle\Service\TestRunnerService;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Templating\EngineInterface;
use Concerto\PanelBundle\Entity\TestSessionLog;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Twig\Environment;
use Twig\Extension\EscaperExtension;
use Twig\Loader\ArrayLoader;

class TestRunnerController
{
    private $templating;
    private $projectDir;
    private $testRunnerService;
    private $logger;
    private $testRunnerSettings;
    private $environment;
    private $sessionRunnerService;
    private $jwtEncoder;

    public function __construct($environment, $projectDir, EngineInterface $templating, TestRunnerService $testRunnerService, LoggerInterface $logger, $testRunnerSettings, ASessionRunnerService $sessionRunnerService, JWTEncoderInterface $encoder)
    {
        $this->templating = $templating;
        $this->testRunnerService = $testRunnerService;
        $this->logger = $logger;
        $this->testRunnerSettings = $testRunnerSettings;
        $this->environment = $environment;
        $this->projectDir = $projectDir;
        $this->sessionRunnerService = $sessionRunnerService;
        $this->jwtEncoder = $encoder;
    }

    /**
     * Returns start new test template.
     *
     * @Route("/test/session/{existing_session_hash}", name="test_runner_test_resume")
     * @Route("/test/{test_slug}/{params}", name="test_runner_test_start", defaults={"params":"{}"})
     * @Route("/test_n/{test_name}/{params}", name="test_runner_test_start_name", defaults={"params":"{}"})
     *
     * @Route("/admin/test/session/{existing_session_hash}/debug", name="test_runner_test_resume_debug", defaults={"debug": true, "protected": true})
     * @Route("/admin/test/{test_slug}/debug/{params}", name="test_runner_test_start_debug", defaults={"params":"{}", "debug": true, "protected": true})
     * @Route("/admin/test/{test_slug}/session/{existing_session_hash}/debug/{params}", name="test_runner_resume_debug", defaults={"params":"{}", "debug": true, "protected": true})
     *
     * @Route("/admin/test/session/{existing_session_hash}", name="test_runner_protected_test_resume", defaults={"protected": true})
     * @Route("/admin/test/{test_slug}/{params}", name="test_runner_protected_test_start", defaults={"params":"{}", "protected": true})
     * @Route("/admin/test_n/{test_name}/{params}", name="test_runner_test_protected_start_name", defaults={"params":"{}", "protected": true})
     *
     * @param Request $request
     * @param SessionInterface $session
     * @param string|null $test_slug
     * @param string|null $test_name
     * @param string $params
     * @param boolean $debug
     * @param string|null $existing_session_hash
     * @return Response
     */
    public function startTestAction(Request $request, SessionInterface $session, $test_slug = null, $test_name = null, $params = "{}", $debug = false, $existing_session_hash = null, $protected = false)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $test_slug, $test_name, $params");

        $locale = $request->get("locale");
        if ($locale && $locale != $request->getLocale()) {
            $request->setLocale($locale);
            $request->setDefaultLocale($locale);
            $session->set("_locale", $locale);
            return new RedirectResponse($request->getUri());
        }

        $params = json_decode($params, true);
        $requestKeys = $request->request->keys();
        foreach ($requestKeys as $k) {
            $params[$k] = $request->request->get($k);
        }
        $queryKeys = $request->query->keys();
        foreach ($queryKeys as $k) {
            $params[$k] = $request->query->get($k);
        }
        $params = json_encode($params);

        $browser_valid = $this->testRunnerService->isBrowserValid($request->headers->get('User-Agent'));
        $baseTemplate = $this->testRunnerService->getBaseTemplateContent($test_slug, $test_name, $existing_session_hash);

        $platformUrl = $this->sessionRunnerService->getPlatformUrl();
        $appUrl = $this->sessionRunnerService->getAppUrl();

        $responseParams = array(
            "platform_url" => $platformUrl,
            "app_url" => $appUrl,
            "test_name" => $test_name,
            "test_slug" => $test_slug,
            "params" => addcslashes($params, "'"),
            "keep_alive_interval" => $this->testRunnerSettings["keep_alive_interval_time"],
            "keep_alive_tolerance" => $this->testRunnerSettings["keep_alive_tolerance_time"],
            "debug" => $debug,
            "protected" => $protected,
            "browser_valid" => $browser_valid,
            "existing_session_hash" => $existing_session_hash
        );

        $template = "ConcertoTestBundle::index.html.twig";
        if ($baseTemplate) {
            $loader = new ArrayLoader([
                "baseTemplate.html.twig" => $baseTemplate
            ]);
            $twig = new Environment($loader);
            $escaper = $twig->getExtension(EscaperExtension::class);
            $escaper->setDefaultStrategy(false);
            $bodyContent = $this->templating->render("@ConcertoTest/test_body.html.twig", $responseParams);
            $responseParams["content"] = $bodyContent;

            $template = $twig->createTemplate($baseTemplate)->render($responseParams);
            $template = str_replace(["<src>", "</src>"], "", trim($template));
            return new Response($template);
        }
        return $this->templating->renderResponse($template, $responseParams);
    }

    /**
     * @Route("/test/{test_slug}/start_session/{params}", name="test_runner_session_start", methods={"POST"}, defaults={"params":"{}"})
     * @Route("/test_n/{test_name}/start_session/{params}", name="test_runner_session_start_name", methods={"POST"}, defaults={"params":"{}"})
     *
     * @Route("/admin/test/{test_slug}/start_session/debug/{params}", name="test_runner_session_start_debug", defaults={"params":"{}", "debug": true}, methods={"POST"})
     *
     * @Route("/admin/test/{test_slug}/start_session/{params}", name="test_runner_protected_session_start", defaults={"params":"{}"}, methods={"POST"})
     *
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

        $result = $this->testRunnerService->startNewSession(
            $test_slug,
            $test_name,
            $params,
            $request->cookies->all(),
            $request->headers->all(),
            $request->getClientIp(),
            $request->server->get('HTTP_USER_AGENT'),
            $debug
        );
        $result["token"] = $this->makeAuthorizationToken($result);
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        $this->setCookies($response, $result);

        return $response;
    }

    /**
     * @Route("/test/session/{session_hash}/resume", name="test_runner_session_resume", methods={"POST"})
     * @param Request $request
     * @param string $session_hash
     * @return RedirectResponse|Response
     */
    public function resumeSessionAction(Request $request, $session_hash)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash");

        if (!$this->checkAuthorizationToken($request, $session_hash)) return new Response("", 403);

        $result = $this->testRunnerService->resumeSession(
            $session_hash,
            $request->cookies->all(),
            $request->getClientIp(),
            $request->server->get('HTTP_USER_AGENT')
        );
        $result["token"] = $this->makeAuthorizationToken($result);
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        $this->setCookies($response, $result);

        return $response;
    }

    /**
     * @Route("/test/session/{session_hash}/submit", name="test_runner_session_submit", methods={"POST"})
     * @param Request $request
     * @param string $session_hash
     * @return RedirectResponse|Response
     */
    public function submitToSessionAction(Request $request, $session_hash)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash");

        if (!$this->checkAuthorizationToken($request, $session_hash)) return new Response("", 403);

        $result = $this->testRunnerService->submitToSession(
            $session_hash,
            $request->get("values"),
            $request->cookies->all(),
            $request->getClientIp(),
            $request->server->get('HTTP_USER_AGENT')
        );
        $result["token"] = $this->makeAuthorizationToken($result);
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        $this->setCookies($response, $result);

        return $response;
    }

    /**
     * @Route("/test/session/{session_hash}/worker", name="test_runner_worker", methods={"POST"})
     * @param Request $request
     * @param string $session_hash
     * @return RedirectResponse|Response
     */
    public function backgroundWorkerAction(Request $request, string $session_hash)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash");

        if (!$this->checkAuthorizationToken($request, $session_hash)) return new Response("", 403);

        $result = $this->testRunnerService->backgroundWorker(
            $session_hash,
            $request->get("values"),
            $request->cookies->all(),
            $request->getClientIp(),
            $request->server->get('HTTP_USER_AGENT')
        );
        $result["token"] = $this->extendAuthorizationToken($request);
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        $this->setCookies($response, $result);

        return $response;
    }

    /**
     * @Route("/test/session/{session_hash}/kill", name="test_runner_session_kill", methods={"POST"})
     * @param Request $request
     * @param $session_hash
     * @return RedirectResponse|Response
     */
    public function killSessionAction(Request $request, $session_hash)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash");

        if (!$this->checkAuthorizationToken($request, $session_hash)) return new Response("", 403);

        $result = $this->testRunnerService->killSession(
            $session_hash,
            $request->getClientIp(),
            $request->server->get('HTTP_USER_AGENT')
        );
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/test/session/{session_hash}/keepalive", name="test_runner_session_keepalive", methods={"POST"})
     * @param Request $request
     * @param string $session_hash
     * @return RedirectResponse|Response
     */
    public function keepAliveSessionAction(Request $request, $session_hash)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash");

        if (!$this->checkAuthorizationToken($request, $session_hash)) return new Response("", 403);

        $result = $this->testRunnerService->keepAliveSession(
            $session_hash,
            $request->getClientIp(),
            $request->server->get('HTTP_USER_AGENT')
        );
        $result["token"] = $this->extendAuthorizationToken($request);
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/test/session/{session_hash}/upload", name="test_runner_upload_file", methods={"POST","OPTIONS"})
     * @param Request $request
     * @param string $session_hash
     * @return RedirectResponse|Response
     */
    public function uploadFileAction(Request $request, $session_hash)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash");

        if (!$this->checkAuthorizationToken($request, $session_hash, false, true)) return new Response("", 403);

        $result = $this->testRunnerService->uploadFile(
            $session_hash,
            $request->files,
            $request->get("name")
        );
        $result["token"] = $this->extendAuthorizationToken($request);
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/files/protected/{name}", name="files_protected", methods={"GET"}, requirements={"name"=".+"})
     * @param Request $request
     * @param string $name
     * @return BinaryFileResponse | Response
     *
     */
    public function getProtectedFile(Request $request, $name)
    {
        if (!$this->checkAuthorizationToken($request, null, true)) return new Response("", 403);

        $dir = "{$this->projectDir}/src/Concerto/PanelBundle/Resources/public/files/protected";
        $realDir = realpath($dir);
        $file = "$realDir/$name";
        if (is_file($file) && realpath($file) === "$realDir/$name") return new BinaryFileResponse($file);

        return new Response("", 404);
    }

    /**
     * @Route("/files/session/{name}", name="files_session", methods={"GET"}, requirements={"name"=".+"})
     * @param Request $request
     * @param string $name
     * @return BinaryFileResponse | Response
     *
     */
    public function getSessionFile(Request $request, $name)
    {
        $sessionHash = $this->getSessionHashFromAuthorizationToken($request);
        if (!$this->checkAuthorizationToken($request, $sessionHash, false, true)) return new Response("", 403);

        $dir = $this->sessionRunnerService->getWorkingDirPath($sessionHash) . "files";
        $realDir = realpath($dir);
        $file = "$realDir/$name";
        if (is_file($file) && realpath($file) === "$realDir/$name") return new BinaryFileResponse($file);

        return new Response("", 404);
    }

    /**
     * @Route("/test/session/{session_hash}/log", name="test_runner_log_error", methods={"POST"})
     * @param Request $request
     * @param string $session_hash
     * @return RedirectResponse|Response
     */
    public function logErrorAction(Request $request, $session_hash)
    {
        if (!$this->checkAuthorizationToken($request, $session_hash, false, false)) return new Response("", 403);

        $result = $this->testRunnerService->logError(
            $session_hash,
            $request->get("error"),
            TestSessionLog::TYPE_JS
        );
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    private function makeAuthorizationToken($result)
    {
        $protectedFilesAccess = false;
        $sessionFilesAccess = false;

        if (!array_key_exists("hash", $result)) return null;
        $sessionHash = $result["hash"];
        if (isset($result["data"]) && is_array($result["data"])) {
            if (isset($result["data"]["protectedFilesAccess"]) && $result["data"]["protectedFilesAccess"] === true) $protectedFilesAccess = true;
            if (isset($result["data"]["sessionFilesAccess"]) && $result["data"]["sessionFilesAccess"] === true) $sessionFilesAccess = true;
        }

        try {
            $token = $this->jwtEncoder->encode([
                "sessionHash" => $sessionHash,
                "protectedFilesAccess" => $protectedFilesAccess,
                "sessionFilesAccess" => $sessionFilesAccess
            ]);
        } catch (JWTEncodeFailureException $e) {
            return null;
        }

        return $token;
    }

    private function extendAuthorizationToken(Request $request)
    {
        try {
            $decodedToken = $this->jwtEncoder->decode($request->get("token"));
        } catch (JWTDecodeFailureException $e) {
            return false;
        }

        try {
            $token = $this->jwtEncoder->encode([
                "sessionHash" => $decodedToken["sessionHash"],
                "protectedFilesAccess" => $decodedToken["protectedFilesAccess"],
                "sessionFilesAccess" => $decodedToken["sessionFilesAccess"]
            ]);
        } catch (JWTEncodeFailureException $e) {
            return false;
        }

        return $token;
    }

    private function checkAuthorizationToken(Request $request, $sessionHash = null, $protectedFilesAccess = false, $sessionFilesAccess = false)
    {
        try {
            $token = $this->jwtEncoder->decode($request->get("token"));
        } catch (JWTDecodeFailureException $e) {
            return false;
        }
        if ($sessionHash !== null && $token["sessionHash"] !== $sessionHash) return false;
        if ($protectedFilesAccess === true && $token["protectedFilesAccess"] !== true) return false;
        if ($sessionFilesAccess === true && $token["sessionFilesAccess"] !== true) return false;
        return true;
    }

    private function getSessionHashFromAuthorizationToken(Request $request)
    {
        try {
            $token = $this->jwtEncoder->decode($request->get("token"));
        } catch (JWTDecodeFailureException $e) {
            return null;
        }
        return $token["sessionHash"];
    }

    private function setCookies(Response &$response, $result)
    {
        if (isset($result["data"]) && is_array($result["data"]) && isset($result["data"]["cookies"])) {
            $cookies = $result["data"]["cookies"];
            if (is_array($cookies)) {
                foreach ($cookies as $k => $v) {
                    $cookie = new Cookie(
                        $k,
                        $v,
                        time() + (30 * 24 * 60 * 60), //30 days
                        '/',
                        null,
                        $this->testRunnerSettings["cookies_secure"] === "true",
                        true,
                        false,
                        $this->testRunnerSettings["cookies_same_site"] ? $this->testRunnerSettings["cookies_same_site"] : null
                    );
                    $response->headers->setCookie($cookie);
                }
            }
        }
    }
}
