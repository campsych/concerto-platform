<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Entity\Test;
use Concerto\PanelBundle\Entity\TestSession;
use Concerto\PanelBundle\Entity\User;
use Concerto\PanelBundle\Repository\TestSessionRepository;
use Concerto\PanelBundle\Repository\TestSessionLogRepository;
use Concerto\PanelBundle\Repository\TestRepository;
use Concerto\TestBundle\Service\ASessionRunnerService;
use Psr\Log\LoggerInterface;
use Concerto\PanelBundle\Entity\TestSessionLog;
use Symfony\Component\Security\Core\Security;

class TestSessionService
{

    const SOURCE_PANEL_NODE = 0;
    const SOURCE_PROCESS = 1;
    const SOURCE_TEST_NODE = 2;
    const RESPONSE_VIEW_TEMPLATE = 0;
    const RESPONSE_FINISHED = 1;
    const RESPONSE_SUBMIT = 2;
    const RESPONSE_STOP = 3;
    const RESPONSE_STOPPED = 4;
    const RESPONSE_VIEW_FINAL_TEMPLATE = 5;
    const RESPONSE_AUTHENTICATION_FAILED = 8;
    const RESPONSE_STARTING = 9;
    const RESPONSE_KEEPALIVE_CHECKIN = 10;
    const RESPONSE_SESSION_LIMIT_REACHED = 12;
    const RESPONSE_TEST_NOT_FOUND = 13;
    const RESPONSE_SESSION_LOST = 14;
    const RESPONSE_WORKER = 15;
    const RESPONSE_RESUME = 16;
    const RESPONSE_ERROR = -1;
    const STATUS_RUNNING = 0;
    const STATUS_FINALIZED = 2;
    const STATUS_ERROR = 3;
    const STATUS_REJECTED = 4;

    public $testSessionRepository;
    private $testRepository;
    private $testSessionLogRepository;
    private $logger;
    private $environment;
    private $secret;
    private $fileService;
    private $testRunnerSettings;
    private $sessionRunnerService;
    private $security;

    public function __construct($environment, TestSessionRepository $testSessionRepository, TestRepository $testRepository, TestSessionLogRepository $testSessionLogRepository, $secret, LoggerInterface $logger, FileService $fileService, $testRunnerSettings, ASessionRunnerService $sessionRunnerService, Security $security)
    {
        $this->environment = $environment;
        $this->testSessionRepository = $testSessionRepository;
        $this->testRepository = $testRepository;
        $this->testSessionLogRepository = $testSessionLogRepository;
        $this->secret = $secret;
        $this->logger = $logger;
        $this->fileService = $fileService;
        $this->testRunnerSettings = $testRunnerSettings;
        $this->sessionRunnerService = $sessionRunnerService;
        $this->security = $security;
    }

    /**
     * @param Test|null $test
     * @return bool
     */
    private function authorizeTest(?Test $test): bool
    {
        if ($test->isProtected()) {
            /* @var User $user */
            $user = $this->security->getUser();
            if (!$user) return false;
        }
        return true;
    }

    private function generateSessionHash($session_id)
    {
        return sha1(time() . "_" . $this->secret . "_" . $session_id);
    }

    public function startNewSession($test_slug, $test_name, $params, $cookies, $headers, $client_ip, $client_browser, $debug, $mustBeRunnable = true, $max_exec_time = null, $authorizationCheck = true)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $test_slug, $test_name, $params, $client_ip, $client_browser, $debug, $mustBeRunnable, $max_exec_time");

        $test = null;
        if ($test_name !== null) {
            $test = $mustBeRunnable ? $this->testRepository->findRunnableByName($test_name) : $this->testRepository->findOneByName($test_name);
        } else {
            $test = $mustBeRunnable ? $this->testRepository->findRunnableBySlug($test_slug) : $this->testRepository->findOneBySlug($test_slug);
        }
        if (!$test || ($authorizationCheck && !$this->authorizeTest($test))) {
            return $this->prepareResponse(null, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_TEST_NOT_FOUND
            ));
        }

        $session = new TestSession();
        $session->setTest($test);
        $session->setClientIp($client_ip);
        $session->setClientBrowser($client_browser);
        $session->setDebug($debug);
        $session->setParams($this->validateParams($session, $params));
        $this->testSessionRepository->save($session);
        $hash = $this->generateSessionHash($session->getId());
        $session->setHash($hash);
        $this->testSessionRepository->save($session);

        $response = $this->sessionRunnerService->startNew($session, $params, $cookies, $headers, $client_ip, $client_browser, $debug, $max_exec_time);
        return $this->prepareResponse($hash, $response);
    }

    public function resumeSession($session_hash, $cookies, $client_ip, $client_browser, $max_exec_time = null)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $client_ip, $client_browser");

        /** @var TestSession $session */
        $session = $this->testSessionRepository->findOneBy(array("hash" => $session_hash));
        if ($session === null) {
            return $this->prepareResponse(null, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_SESSION_LOST
            ));
        }

        $response = $this->sessionRunnerService->resume($session, $cookies, $client_ip, $client_browser, $max_exec_time);
        return $this->prepareResponse($session_hash, $response);
    }

    private function validateParams($session, $params)
    {
        $result = array();

        //setting default values
        foreach ($session->getTest()->getVariables() as $var) {
            if ($var->getType() === 0 && $var->isPassableThroughUrl() && $var->hasDefaultValueSet()) {
                $result[$var->getName()] = $var->getValue();
            }
        }

        $dp = json_decode($params, true);
        if ($dp != null) {
            foreach ($dp as $k => $v) {
                foreach ($session->getTest()->getVariables() as $var) {
                    if ($var->getType() === 0 && $var->isPassableThroughUrl() && $k === $var->getName()) {
                        $result[$k] = $v;
                        break;
                    }
                }
            }
        }
        return json_encode($result);
    }

    public function setTemplateTimerValues(TestSession $session, $values)
    {
        $time = microtime(true);

        $timeLimit = $session->getTimeLimit();
        $timeTaken = $values["timeTaken"];
        $isTimeout = 0;
        if (isset($values["isTimeout"])) {
            $isTimeout = $values["isTimeout"];
        }
        if ($this->testRunnerSettings["timer_type"] == "server") {
            $timeTaken = $this->testSessionRepository->getUpdatedAgo($session->getId());
            if ($timeLimit > 0 && $timeTaken >= $timeLimit) {
                $isTimeout = 1;
            }
        }
        if ($isTimeout) {
            $timeTaken = $timeLimit;
        }

        $values["timeTaken"] = $timeTaken;
        $values["isTimeout"] = $isTimeout;
        return $values;
    }

    public function submit($session_hash, $values, $cookies, $client_ip, $client_browser)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $client_ip, $client_browser");

        $session = $this->testSessionRepository->findOneBy(array("hash" => $session_hash));
        if (!$session) {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - session $session_hash not found.");
            return $this->prepareResponse($session_hash, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_ERROR
            ));
        }

        if ($session->getStatus() !== TestSession::STATUS_RUNNING) {
            return $this->prepareResponse($session_hash, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_SESSION_LOST
            ));
        }

        $values = $this->setTemplateTimerValues($session, $values);

        $session->setClientIp($client_ip);
        $session->setClientBrowser($client_browser);
        $this->testSessionRepository->save($session);

        $response = $this->sessionRunnerService->submit($session, $values, $cookies, $client_ip, $client_browser);
        return $this->prepareResponse($session_hash, $response);
    }

    public function backgroundWorker($session_hash, $values, $cookies, $client_ip, $client_browser)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $client_ip, $client_browser");

        $session = $this->testSessionRepository->findOneBy(array("hash" => $session_hash));
        if (!$session) {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - session $session_hash not found.");
            return $this->prepareResponse($session_hash, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_ERROR
            ));
        }

        if ($session->getStatus() !== TestSession::STATUS_RUNNING) {
            return $this->prepareResponse($session_hash, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_SESSION_LOST
            ));
        }

        $values = $this->setTemplateTimerValues($session, $values);

        $session->setClientIp($client_ip);
        $session->setClientBrowser($client_browser);
        $this->testSessionRepository->save($session);

        $response = $this->sessionRunnerService->backgroundWorker($session, $values, $cookies, $client_ip, $client_browser);
        return $this->prepareResponse($session_hash, $response);
    }

    public function keepAlive($session_hash, $client_ip, $client_browser)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $client_ip, $client_browser");

        $session = $this->testSessionRepository->findOneBy(array("hash" => $session_hash));
        if (!$session) {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - session $session_hash not found.");
            return $this->prepareResponse($session_hash, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_ERROR
            ));
        }

        if ($session->getStatus() !== TestSession::STATUS_RUNNING) {
            return $this->prepareResponse($session_hash, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_SESSION_LOST
            ));
        }

        $response = $this->sessionRunnerService->keepAlive($session, $client_ip, $client_browser);
        return $response;
    }

    public function kill($session_hash, $client_ip, $client_browser)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $client_ip");

        $session = $this->testSessionRepository->findOneBy(array("hash" => $session_hash));
        if (!$session) {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - session $session_hash not found.");
            return $this->prepareResponse($session_hash, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_ERROR
            ));
        }

        if ($session->getStatus() !== TestSession::STATUS_RUNNING) {
            return $this->prepareResponse($session_hash, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_SESSION_LOST
            ));
        }

        $response = $this->sessionRunnerService->kill($session, $client_ip, $client_browser);
        return $response;
    }

    public function uploadFile($session_hash, $files, $name)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $name");

        $session = $this->testSessionRepository->findOneBy(array("hash" => $session_hash));
        if (!$session) {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - session $session_hash not found.");
            return $this->prepareResponse($session_hash, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_ERROR
            ));
        }

        if ($session->getStatus() !== TestSession::STATUS_RUNNING) {
            return $this->prepareResponse($session_hash, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_SESSION_LOST
            ));
        }

        $message = "";
        $upload_result = $this->fileService->uploadFiles(FileService::DIR_SESSION, "", $files, $message, $session_hash);
        if ($upload_result) {
            foreach ($files as $file) {
                $response = array("result" => 0, "file_path" => realpath($this->fileService->getSessionUploadDirectory($session_hash) . $file->getClientOriginalName()), "name" => $name);
                return $response;
            }
        }
        $response = array("result" => -1, "error" => $message);
        return $response;
    }

    private function saveErrorLog(TestSession $session, $error, $type)
    {
        $this->logger->error($session->getHash() . ", $error");

        $log = new TestSessionLog();
        $log->setBrowser($session->getClientBrowser());
        $log->setIp($session->getClientIp());
        $log->setMessage($error);
        $log->setType($type);
        $log->setTest($session->getTest());
        $this->testSessionLogRepository->save($log);
    }

    private function prepareResponse($session_hash, $response)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash");
        if ($session_hash !== null) {
            $session = $this->testSessionRepository->findOneBy(array("hash" => $session_hash));
            if ($session !== null) {
                if (isset($response["systemError"])) {
                    $this->saveErrorLog($session, $response["systemError"], TestSessionLog::TYPE_SYSTEM);
                }

                switch ($response["code"]) {
                    case self::RESPONSE_ERROR:
                        if ($session->getError()) {
                            $this->saveErrorLog($session, $session->getError(), TestSessionLog::TYPE_R);
                        }
                        break;
                    case self::RESPONSE_VIEW_FINAL_TEMPLATE:
                    case self::RESPONSE_VIEW_TEMPLATE:
                        $response["timeLimit"] = $session->getTimeLimit();
                        break;
                    case self::RESPONSE_WORKER:
                        break;
                }
                $response["hash"] = $session_hash;
            }
        }
        return $response;
    }

    public function logError($session_hash, $error, $type)
    {
        $session = $this->testSessionRepository->findOneBy(array("hash" => $session_hash));
        if (!$session) {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - session $session_hash not found.");
            return $this->prepareResponse($session_hash, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_ERROR
            ));
        }

        if ($session->getStatus() !== TestSession::STATUS_RUNNING) {
            return $this->prepareResponse($session_hash, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_SESSION_LOST
            ));
        }

        //@TODO unify response
        $this->saveErrorLog($session, $error, $type);
        $response = array("result" => 0);
        return $response;
    }

}
