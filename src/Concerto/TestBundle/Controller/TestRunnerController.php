<?php

namespace Concerto\TestBundle\Controller;

use Concerto\TestBundle\Service\ASessionRunnerService;
use Concerto\TestBundle\Service\TestRunnerService;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException;
use Symfony\Component\Filesystem\Filesystem;
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
     * @Route("/test/session/{existing_session_hash}", name="test_runner_test_resume", defaults={"test_slug":null,"test_name":null,"params":"{}","debug":false})
     * @Route("/test/session/{existing_session_hash}", name="test_runner_test_resume_name", defaults={"test_slug":null,"test_name":null,"params":"{}","debug":false})
     * @Route("/test/{test_slug}/{params}", name="test_runner_test_start", defaults={"test_name":null,"params":"{}","debug":false})
     * @Route("/test_n/{test_name}/{params}", name="test_runner_test_start_name", defaults={"test_slug":null,"params":"{}"})
     * @param Request $request
     * @param SessionInterface $session
     * @param string|null $test_slug
     * @param string|null $test_name
     * @param string $params
     * @param boolean $debug
     * @param string|null $existing_session_hash
     * @return Response
     */
    public function startNewTestAction(Request $request, SessionInterface $session, $test_slug = null, $test_name = null, $params = "{}", $debug = false, $existing_session_hash = null)
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
        $keys = $request->query->keys();
        foreach ($keys as $k) {
            $params[$k] = $request->query->get($k);
        }
        $params = json_encode($params);
        $browser_valid = $this->testRunnerService->isBrowserValid($request->headers->get('User-Agent'));
        $baseTemplate = $this->testRunnerService->getBaseTemplateContent($test_slug, $test_name);

        $platformUrl = $this->sessionRunnerService->getPlatformUrl();
        $appUrl = $this->sessionRunnerService->getAppUrl();

        $responseParams = array(
            "platform_url" => $platformUrl,
            "app_url" => $appUrl,
            "test_name" => $test_name,
            "test_slug" => $test_slug,
            "params" => addcslashes($params, "'"),
            "keep_alive_interval" => $this->testRunnerSettings["keep_alive_interval_time"],
            "debug" => $debug,
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
            return new Response($template);
        }
        return $this->templating->renderResponse($template, $responseParams);
    }

    /**
     * @Route("/admin/test/{test_slug}/debug/{params}", name="test_runner_test_start_debug", defaults={"params":"{}"})
     * @Route("/admin/test/{test_slug}/session/{existing_session_hash}/debug/{params}", name="test_runner_resume_debug", defaults={"params":"{}"})
     * @param Request $request
     * @param SessionInterface $session
     * @param string $test_slug
     * @param string $params
     * @param string|null $existing_session_hash
     * @return Response
     */
    public function startNewDebugTestAction(Request $request, SessionInterface $session, $test_slug, $params = "{}", $existing_session_hash = null)
    {
        return $this->startNewTestAction($request, $session, $test_slug, null, $params, true, $existing_session_hash);
    }

    /**
     * @Route("/test/{test_slug}/start_session/{params}", name="test_runner_session_start", defaults={"test_name":null,"params":"{}","debug":false}, methods={"POST"})
     * @Route("/test_n/{test_name}/start_session/{params}", name="test_runner_session_start_name", defaults={"test_slug":null,"params":"{}","debug":false}, methods={"POST"})
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
            $request->getClientIp(),
            $request->server->get('HTTP_USER_AGENT'),
            $debug
        );
        $response = new Response($result);
        $response->headers->set('Content-Type', 'application/json');
        $this->setAuthorizationCookie($response, $result);
        $this->setCookies($response, $result);

        return $response;
    }

    /**
     * @Route("/admin/test/{test_slug}/start_session/debug/{params}", name="test_runner_session_start_debug", defaults={"params":"{}"}, methods={"POST"})
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
     * @Route("/test/session/{session_hash}/resume", name="test_runner_session_resume", defaults={"debug":false}, methods={"POST"})
     * @param Request $request
     * @param string $session_hash
     * @param bool $debug
     * @return RedirectResponse|Response
     */
    public function resumeSessionAction(Request $request, $session_hash, $debug = false)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $debug");

        if (!$this->checkAuthorizationCookie($request, $session_hash)) return new Response("", 403);

        $result = $this->testRunnerService->resumeSession(
            $session_hash,
            $request->cookies->all(),
            $request->getClientIp(),
            $request->server->get('HTTP_USER_AGENT'),
            $debug
        );
        $response = new Response($result);
        $response->headers->set('Content-Type', 'application/json');
        $this->setAuthorizationCookie($response, $result);
        $this->setCookies($response, $result);

        return $response;
    }

    /**
     * @Route("/admin/test/session/{session_hash}/resume/debug", name="test_runner_session_resume_debug", methods={"POST"})
     * @param Request $request
     * @param string $session_hash
     * @return RedirectResponse|Response
     */
    public function resumeDebugSessionAction(Request $request, $session_hash)
    {
        return $this->resumeSessionAction($request, $session_hash, true);
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

        if (!$this->checkAuthorizationCookie($request, $session_hash)) return new Response("", 403);

        $result = $this->testRunnerService->submitToSession(
            $session_hash,
            $request->get("values"),
            $request->cookies->all(),
            $request->getClientIp(),
            $request->server->get('HTTP_USER_AGENT')
        );
        $response = new Response($result);
        $response->headers->set('Content-Type', 'application/json');
        $this->setAuthorizationCookie($response, $result);
        $this->setCookies($response, $result);

        return $response;
    }

    /**
     * @Route("/test/session/{session_hash}/worker", name="test_runner_worker", methods={"POST"})
     * @param Request $request
     * @param string $session_hash
     * @return RedirectResponse|Response
     */
    public function backgroundWorkerAction(Request $request, $session_hash)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash");

        if (!$this->checkAuthorizationCookie($request, $session_hash)) return new Response("", 403);

        $result = $this->testRunnerService->backgroundWorker(
            $session_hash,
            $request->get("values"),
            $request->cookies->all(),
            $request->getClientIp(),
            $request->server->get('HTTP_USER_AGENT')
        );
        $response = new Response($result);
        $response->headers->set('Content-Type', 'application/json');
        $this->setAuthorizationCookie($response, $result);
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

        if (!$this->checkAuthorizationCookie($request, $session_hash)) return new Response("", 403);

        $result = $this->testRunnerService->killSession(
            $session_hash,
            $request->getClientIp(),
            $request->server->get('HTTP_USER_AGENT')
        );
        $response = new Response($result);
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

        if (!$this->checkAuthorizationCookie($request, $session_hash)) return new Response("", 403);

        $result = $this->testRunnerService->keepAliveSession(
            $session_hash,
            $request->getClientIp(),
            $request->server->get('HTTP_USER_AGENT')
        );
        $response = new Response($result);
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

        if (!$this->checkAuthorizationCookie($request, $session_hash, false, true)) return new Response("", 403);

        $result = $this->testRunnerService->uploadFile(
            $session_hash,
            $request->files,
            $request->get("name")
        );
        $response = new Response($result);
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
        if (!$this->checkAuthorizationCookie($request, null, true)) return new Response("", 403);

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
        $sessionHash = $this->getSessionHashFromAuthorizationCookie($request);
        if (!$this->checkAuthorizationCookie($request, $sessionHash, false, true)) return new Response("", 403);

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
        if (!$this->checkAuthorizationCookie($request, null, true, false)) return new Response("", 403);

        $result = $this->testRunnerService->logError(
            $session_hash,
            $request->get("error"),
            TestSessionLog::TYPE_JS
        );
        $response = new Response($result);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    private function setAuthorizationCookie(&$response, $result)
    {
        $protectedFilesAccess = false;
        $sessionFilesAccess = false;
        $sessionHash = null;

        $decodedResult = json_decode($result, true);
        $sessionHash = $decodedResult["hash"];
        if (array_key_exists("data", $decodedResult) && is_array($decodedResult["data"])) {
            if (array_key_exists("protectedFilesAccess", $decodedResult["data"]) && $decodedResult["data"]["protectedFilesAccess"] === true) $protectedFilesAccess = true;
            if (array_key_exists("sessionFilesAccess", $decodedResult["data"]) && $decodedResult["data"]["sessionFilesAccess"] === true) $sessionFilesAccess = true;
        }

        $token = null;
        try {
            $token = $this->jwtEncoder->encode([
                "sessionHash" => $sessionHash,
                "protectedFilesAccess" => $protectedFilesAccess,
                "sessionFilesAccess" => $sessionFilesAccess,
                "expiry" => time() + 3600
            ]);
        } catch (JWTEncodeFailureException $e) {
            return false;
        }

        $response->headers->setCookie(new Cookie("concertoSession", $token));
        return true;
    }

    private function checkAuthorizationCookie(Request $request, $sessionHash = null, $protectedFilesAccess = false, $sessionFilesAccess = false)
    {
        $token = null;
        try {
            $token = $this->jwtEncoder->decode($request->cookies->get("concertoSession"));
        } catch (JWTDecodeFailureException $e) {
            return false;
        }
        if ($sessionHash !== null && $token["sessionHash"] !== $sessionHash) return false;
        if ($protectedFilesAccess === true && $token["protectedFilesAccess"] !== true) return false;
        if ($sessionFilesAccess === true && $token["sessionFilesAccess"] !== true) return false;
        if (time() > $token["expiry"]) return false;
        return true;
    }

    private function getSessionHashFromAuthorizationCookie(Request $request)
    {
        $token = null;
        try {
            $token = $this->jwtEncoder->decode($request->cookies->get("concertoSession"));
        } catch (JWTDecodeFailureException $e) {
            return null;
        }
        return $token["sessionHash"];
    }

    private function setCookies(&$response, $result)
    {
        $decodedResult = json_decode($result, true);
        if (array_key_exists("data", $decodedResult) && is_array($decodedResult["data"]) && array_key_exists("cookies", $decodedResult["data"])) {
            $cookies = $decodedResult["data"]["cookies"];
            if (is_array($cookies)) {
                foreach ($cookies as $k => $v) {
                    $response->headers->setCookie(new Cookie($k, $v));
                }
            }
        }
    }
}
