<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Entity\TestSession;
use Concerto\PanelBundle\Repository\TestSessionRepository;
use Concerto\PanelBundle\Repository\TestSessionLogRepository;
use Concerto\PanelBundle\Repository\TestRepository;
use Psr\Log\LoggerInterface;
use Concerto\PanelBundle\Entity\TestSessionLog;
use Concerto\TestBundle\Service\RRunnerService;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

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
    const RESPONSE_RESULTS = 7;
    const RESPONSE_AUTHENTICATION_FAILED = 8;
    const RESPONSE_STARTING = 9;
    const RESPONSE_KEEPALIVE_CHECKIN = 10;
    const RESPONSE_UNRESUMABLE = 11;
    const RESPONSE_SESSION_LIMIT_REACHED = 12;
    const RESPONSE_TEST_NOT_FOUND = 13;
    const RESPONSE_SESSION_LOST = 14;
    const RESPONSE_WORKER = 15;
    const RESPONSE_ERROR = -1;
    const STATUS_RUNNING = 0;
    const STATUS_FINALIZED = 2;
    const STATUS_ERROR = 3;
    const STATUS_REJECTED = 4;

    private $testSessionRepository;
    private $testRepository;
    private $testSessionLogRepository;
    private $logger;
    private $panelNodes;
    private $environment;
    private $secret;
    private $rRunnerService;
    private $fileService;
    private $administrationService;
    private $loadBalancerService;
    private $testRunnerSettings;
    private $session;

    public function __construct($environment, TestSessionRepository $testSessionRepository, TestRepository $testRepository, TestSessionLogRepository $testSessionLogRepository, $panelNodes, $secret, LoggerInterface $logger, RRunnerService $rRunnerService, FileService $fileService, AdministrationService $administrationService, LoadBalancerInterface $loadBalancerService, $testRunnerSettings, SessionInterface $session)
    {
        $this->environment = $environment;
        $this->testSessionRepository = $testSessionRepository;
        $this->testRepository = $testRepository;
        $this->testSessionLogRepository = $testSessionLogRepository;
        $this->panelNodes = $panelNodes;
        $this->secret = $secret;
        $this->logger = $logger;
        $this->rRunnerService = $rRunnerService;
        $this->fileService = $fileService;
        $this->administrationService = $administrationService;
        $this->loadBalancerService = $loadBalancerService;
        $this->testRunnerSettings = $testRunnerSettings;
        $this->session = $session;
    }

    private function getLocalPanelNode()
    {
        foreach ($this->panelNodes as $node) {
            if ($node["local"] == "true")
                return $node;
        }
        return $this->panelNodes[0];
    }

    private function generateSessionHash($session_id)
    {
        return sha1(time() . "_" . $this->secret . "_" . $session_id);
    }

    public function startNewSession($test_node_hash, $test_slug, $test_name, $params, $client_ip, $client_browser, $calling_node_ip, $debug)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $test_node_hash, $test_slug, $test_name, $params, $client_ip, $client_browser, $calling_node_ip, $debug");

        $test_node = $this->loadBalancerService->authorizeTestNode($calling_node_ip, $test_node_hash);
        if (!$test_node) {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - node $test_node_hash / $calling_node_ip authentication failed.");
            return $this->prepareResponse(null, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_AUTHENTICATION_FAILED
            ));
        }

        $test = null;
        if ($test_name !== null) {
            $test = $this->testRepository->findRunnableByName($test_name);
        } else {
            $test = $this->testRepository->findRunnableBySlug($test_slug);
        }
        if (!$test) {
            return $this->prepareResponse(null, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_TEST_NOT_FOUND
            ));
        }

        $panel_node = $this->getLocalPanelNode();
        if (($panel_node_sock = $this->createListenerSocket(gethostbyname($panel_node["sock_host"]), null)) === false) {
            return $this->prepareResponse(null, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_ERROR
            ));
        }
        $panel_node_host = "";
        $panel_node_port = 0;
        socket_getsockname($panel_node_sock, $panel_node_host, $panel_node_port);

        $session = new TestSession();
        $session->setTest($test);
        $session->setClientIp($client_ip);
        $session->setClientBrowser($client_browser);
        $session->setPanelNodeId($panel_node["id"]);
        $session->setPanelNodePort($panel_node_port);
        $session->setTestNodeId($test_node["id"]);
        $session->setDebug($debug);
        $session->setParams($this->validateParams($session, $params));
        $this->testSessionRepository->save($session);
        $hash = $this->generateSessionHash($session->getId());
        $session->setHash($hash);
        $this->testSessionRepository->save($session);

        $rresult = $this->initiateTestNode($session->getHash(), $panel_node, $panel_node_port, $test_node, $client_ip, $client_browser, $debug);
        $this->testSessionRepository->clear();

        $session = $this->testSessionRepository->findOneBy(array("hash" => $hash));
        $response = null;
        switch ($rresult["code"]) {
            case self::RESPONSE_AUTHENTICATION_FAILED:
                {
                    $response = $rresult;
                    $session->setStatus(self::STATUS_REJECTED);
                    $this->testSessionRepository->save($session);
                    socket_close($panel_node_sock);
                    break;
                }
            case self::RESPONSE_SESSION_LIMIT_REACHED:
                {
                    $response = $rresult;
                    $session->setStatus(self::STATUS_REJECTED);
                    $this->testSessionRepository->save($session);
                    $this->administrationService->insertSessionLimitMessage($session);
                    socket_close($panel_node_sock);
                    break;
                }
            default:
                {
                    $response = $this->startListener($panel_node_sock, $hash);
                    $this->testSessionRepository->clear();
                    break;
                }
        }
        return $this->prepareResponse($hash, $response);
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

    public function setTemplateTimerValues(TestSession $session, $values, $time = null)
    {
        if ($time === null) $time = microtime(true);
        $values = json_decode($values, true);

        $timeLimit = $session->getTimeLimit();
        $timeTaken = $values["timeTaken"];
        $isTimeout = 0;
        if (array_key_exists("isTimeout", $values)) {
            $isTimeout = $values["isTimeout"];
        }
        if ($this->testRunnerSettings["timer_type"] == "server") {
            $timeTaken = $time - $this->session->get("templateStartTime");
            if ($timeLimit > 0 && $timeTaken >= $timeLimit) {
                $isTimeout = 1;
            }
        }
        if ($isTimeout) {
            $timeTaken = $timeLimit;
        }

        $values["timeTaken"] = $timeTaken;
        $values["isTimeout"] = $isTimeout;
        return json_encode($values);
    }

    public function submit($session_hash, $values, $client_ip, $client_browser, $calling_node_ip, $time)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $values, $client_ip, $client_browser, $calling_node_ip");

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

        $test_node = $this->loadBalancerService->getTestNodeById($session->getTestNodeId());
        if (!$test_node) {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - test node " . $test_node["hash"] . " not found.");
            return $this->prepareResponse($session_hash, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_ERROR
            ));
        }

        $test_node = $this->loadBalancerService->authorizeTestNode($calling_node_ip, $test_node["hash"]);
        if (!$test_node) {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - test node " . $test_node["hash"] . "  $calling_node_ip authentication failed.");
            return $this->prepareResponse($session_hash, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_AUTHENTICATION_FAILED
            ));
        }

        $values = $this->setTemplateTimerValues($session, $values, $time);

        $panel_node = $this->getLocalPanelNode();
        if (($client_sock = $this->createListenerSocket(gethostbyname($panel_node["sock_host"]), $session_hash)) === false) {
            return $this->prepareResponse($session_hash, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_ERROR
            ));
        }
        socket_getsockname($client_sock, $panel_node_ip, $panel_node_port);

        $test_node_port = $session->getTestNodePort();

        $session->setPanelNodePort($panel_node_port);
        $session->setClientIp($client_ip);
        $session->setClientBrowser($client_browser);
        $session->setUpdated();
        $this->testSessionRepository->save($session);
        $this->testSessionRepository->clear();

        $submitted = $this->submitToTestNode($test_node, $test_node_port, $panel_node, $panel_node_port, $client_ip, $client_browser, $values);
        if (!$submitted) {
            return $this->prepareResponse($session_hash, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_SESSION_LOST
            ), "Submit to process failed.");
        }

        $response = $this->startListener($client_sock, $session_hash);

        return $this->prepareResponse($session_hash, $response);
    }

    public function backgroundWorker($session_hash, $values, $client_ip, $client_browser, $calling_node_ip, $time)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $values, $client_ip, $client_browser, $calling_node_ip");

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

        $test_node = $this->loadBalancerService->getTestNodeById($session->getTestNodeId());
        if (!$test_node) {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - test node " . $test_node["hash"] . " not found.");
            return $this->prepareResponse($session_hash, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_ERROR
            ));
        }

        $test_node = $this->loadBalancerService->authorizeTestNode($calling_node_ip, $test_node["hash"]);
        if (!$test_node) {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - test node " . $test_node["hash"] . "  $calling_node_ip authentication failed.");
            return $this->prepareResponse($session_hash, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_AUTHENTICATION_FAILED
            ));
        }

        $values = $this->setTemplateTimerValues($session, $values, $time);

        $panel_node = $this->getLocalPanelNode();
        if (($client_sock = $this->createListenerSocket(gethostbyname($panel_node["sock_host"]), $session_hash)) === false) {
            return $this->prepareResponse($session_hash, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_ERROR
            ));
        }
        socket_getsockname($client_sock, $panel_node_ip, $panel_node_port);

        $test_node_port = $session->getTestNodePort();

        $session->setPanelNodePort($panel_node_port);
        $session->setClientIp($client_ip);
        $session->setClientBrowser($client_browser);
        $session->setUpdated();
        $this->testSessionRepository->save($session);
        $this->testSessionRepository->clear();

        $submitted = $this->submitToTestNode($test_node, $test_node_port, $panel_node, $panel_node_port, $client_ip, $client_browser, $values, self::RESPONSE_WORKER);
        if (!$submitted) {
            return $this->prepareResponse($session_hash, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_SESSION_LOST
            ), "Submit to process failed.");
        }

        $response = $this->startListener($client_sock, $session_hash);

        return $this->prepareResponse($session_hash, $response);
    }

    public function keepAlive($session_hash, $client_ip, $calling_node_ip)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $client_ip, $calling_node_ip");

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

        $test_node = $this->loadBalancerService->getTestNodeById($session->getTestNodeId());
        if (!$test_node) {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - test node " . $test_node["hash"] . " not found.");
            return $this->prepareResponse($session_hash, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_ERROR
            ));
        }

        $test_node = $this->loadBalancerService->authorizeTestNode($calling_node_ip, $test_node["hash"]);
        if (!$test_node) {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - test node " . $test_node["hash"] . "  $calling_node_ip authentication failed.");
            return $this->prepareResponse($session_hash, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_AUTHENTICATION_FAILED
            ));
        }

        $panel_node = $this->getLocalPanelNode();
        $test_node_port = $session->getTestNodePort();

        $checked = $this->keepAliveTestNode($test_node, $test_node_port, $panel_node, $client_ip);
        if (!$checked) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, keep alive check in to test node failed.");
            return $this->prepareResponse($session_hash, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_SESSION_LOST
            ));
        }

        return $this->prepareResponse($session_hash, array(
            "source" => self::SOURCE_PANEL_NODE,
            "code" => self::RESPONSE_KEEPALIVE_CHECKIN
        ));
    }

    public function kill($session_hash, $client_ip, $calling_node_ip)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $client_ip, $calling_node_ip");

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

        $test_node = $this->loadBalancerService->getTestNodeById($session->getTestNodeId());
        if (!$test_node) {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - test node " . $test_node["hash"] . " not found.");
            return $this->prepareResponse($session_hash, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_ERROR
            ));
        }

        $test_node = $this->loadBalancerService->authorizeTestNode($calling_node_ip, $test_node["hash"]);
        if (!$test_node) {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - test node " . $test_node["hash"] . "  $calling_node_ip authentication failed.");
            return $this->prepareResponse($session_hash, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_AUTHENTICATION_FAILED
            ));
        }

        $panel_node = $this->getLocalPanelNode();
        $test_node_port = $session->getTestNodePort();

        $killed = $this->killTestNode($test_node, $test_node_port, $panel_node, $client_ip);
        if (!$killed) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, killing test node failed.");
            return $this->prepareResponse($session_hash, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_SESSION_LOST
            ));
        }

        return $this->prepareResponse($session_hash, array(
            "source" => self::SOURCE_PANEL_NODE,
            "code" => self::RESPONSE_STOPPED
        ));
    }

    public function results($session_hash, $calling_node_ip)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $calling_node_ip");

        $session = $this->testSessionRepository->findOneBy(array("hash" => $session_hash));
        if (!$session) {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - session $session_hash not found.");
            return $this->prepareResponse($session_hash, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_ERROR
            ));
        }

        $test_node = $this->loadBalancerService->getTestNodeById($session->getTestNodeId());
        if (!$test_node) {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - test node " . $test_node["hash"] . " not found.");
            return $this->prepareResponse($session_hash, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_ERROR
            ));
        }

        $test_node = $this->loadBalancerService->authorizeTestNode($calling_node_ip, $test_node["hash"]);
        if (!$test_node) {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - test node " . $test_node["hash"] . "  $calling_node_ip authentication failed.");
            return $this->prepareResponse($session_hash, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_AUTHENTICATION_FAILED
            ));
        }
        return $this->prepareResponse($session_hash, array(
            "source" => self::SOURCE_PANEL_NODE,
            "code" => self::RESPONSE_RESULTS
        ));
    }

    public function uploadFile($session_hash, $calling_node_ip, $files, $name)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $calling_node_ip, $name");

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

        $test_node = $this->loadBalancerService->getTestNodeById($session->getTestNodeId());
        if (!$test_node) {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - test node " . $test_node["hash"] . "not found.");
            return $this->prepareResponse($session_hash, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_ERROR
            ));
        }

        $test_node = $this->loadBalancerService->authorizeTestNode($calling_node_ip, $test_node["hash"]);
        if (!$test_node) {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - test node " . $test_node["hash"] . "  $calling_node_ip authentication failed.");
            return $this->prepareResponse($session_hash, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_AUTHENTICATION_FAILED
            ));
        }

        //@TODO unify response
        $response = array("result" => -1);
        foreach ($files as $file) {
            $upload_result = $this->fileService->moveUploadedFile($file->getRealPath(), FileService::DIR_PRIVATE, $file->getClientOriginalName(), $message);
            if ($upload_result)
                $response = array("result" => 0, "file_path" => realpath($this->fileService->getPrivateUploadDirectory() . $file->getClientOriginalName()), "name" => $name);
            else {
                $response = array("result" => -1, "error" => $message);
                return $response;
            }
        }
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

    private function prepareResponse($session_hash, $response, $system_error = null)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash");
        if ($session_hash !== null) {
            $session = $this->testSessionRepository->findOneBy(array("hash" => $session_hash));
            if ($session !== null) {
                if ($system_error !== null) {
                    $this->saveErrorLog($session, $system_error, TestSessionLog::TYPE_SYSTEM);
                }

                switch ($response["code"]) {
                    case self::RESPONSE_ERROR:
                        if ($session->getError()) {
                            $this->saveErrorLog($session, $session->getError(), TestSessionLog::TYPE_R);
                        }
                        break;
                    case self::RESPONSE_RESULTS:
                        $response["results"] = $session->getReturns();
                        break;
                    case self::RESPONSE_VIEW_FINAL_TEMPLATE:
                    case self::RESPONSE_VIEW_TEMPLATE:
                        $response["results"] = $session->getReturns();
                        $response["timeLimit"] = $session->getTimeLimit();
                        $response["templateHead"] = $session->getTemplateHead();
                        $response["templateCss"] = $session->getTemplateCss();
                        $response["templateJs"] = $session->getTemplateJs();
                        $response["templateHtml"] = $session->getTemplateHtml();
                        $response["loaderHead"] = $session->getLoaderHead();
                        $response["loaderCss"] = $session->getLoaderCss();
                        $response["loaderJs"] = $session->getLoaderJs();
                        $response["loaderHtml"] = $session->getLoaderHtml();
                        $response["templateParams"] = $session->getTemplateParams();
                        break;
                    case self::RESPONSE_WORKER:
                        break;
                }
                $response["hash"] = $session_hash;
            }
        }
        return $response;
    }

    private function submitToTestNode($test_node, $test_node_port, $panel_node, $panel_node_port, $client_ip, $client_browser, $values, $code = self::RESPONSE_SUBMIT)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - " . $test_node["sock_host"] . ", $test_node_port, " . $panel_node["sock_host"] . ", $panel_node_port, $client_ip, $client_browser, $values");
        if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
            return false;
        }
        if (socket_connect($sock, gethostbyname($test_node["sock_host"]), $test_node_port) === false) {
            socket_close($sock);
            return false;
        }
        socket_write($sock, json_encode(array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => $code,
                "panelNode" => array("sock_host" => $panel_node["sock_host"], "port" => $panel_node_port, "client_ip" => $client_ip, "client_browser" => $client_browser),
                "values" => $values
            )) . "\n");
        socket_close($sock);
        return true;
    }

    private function keepAliveTestNode($test_node, $test_node_port, $panel_node, $client_ip)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - " . $test_node["sock_host"] . ", $test_node_port, " . $panel_node["sock_host"] . ", $client_ip");
        if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
            return false;
        }
        if (socket_connect($sock, gethostbyname($test_node["sock_host"]), $test_node_port) === false) {
            socket_close($sock);
            return false;
        }
        socket_write($sock, json_encode(array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_KEEPALIVE_CHECKIN,
                "panelNode" => array("sock_host" => $panel_node["sock_host"], "client_ip" => $client_ip)
            )) . "\n");
        socket_close($sock);
        return true;
    }

    private function killTestNode($test_node, $test_node_port, $panel_node, $client_ip)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - " . $test_node["sock_host"] . ", $test_node_port, " . $panel_node["sock_host"] . ", $client_ip");
        if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
            return false;
        }
        if (socket_connect($sock, gethostbyname($test_node["sock_host"]), $test_node_port) === false) {
            socket_close($sock);
            return false;
        }
        socket_write($sock, json_encode(array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_STOP,
                "panelNode" => array("sock_host" => $panel_node["sock_host"], "client_ip" => $client_ip)
            )) . "\n");
        socket_close($sock);
        return true;
    }

    private function createListenerSocket($ip, $session_hash)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $ip");
        if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . ":socket_create() failed, " . socket_strerror(socket_last_error()) . ", $session_hash");
            return false;
        }
        if (socket_bind($sock, "0.0.0.0") === false) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . ":socket_bind() failed, " . socket_strerror(socket_last_error($sock)) . ", $session_hash");
            socket_close($sock);
            return false;
        }
        if (socket_listen($sock, SOMAXCONN) === false) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . ":socket_listen() failed, " . socket_strerror(socket_last_error($sock)) . ", $session_hash");
            socket_close($sock);
            return false;
        }
        return $sock;
    }

    private function initiateTestNode($session_hash, $panel_node, $panel_node_port, $test_node, $client_ip, $client_browser, $debug, $values = null)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $panel_node_port, $client_ip, $client_browser, $debug, $values");

        if ($test_node["local"] != "true") {
            $web_host = $test_node["web_host"];
            if (array_key_exists("internal_web_host", $test_node)) {
                $web_host = $test_node["internal_web_host"];
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $test_node["protocol"] . "://$web_host:" . $test_node["web_port"] . $test_node["dir"] . ($this->environment == "prod" ? "" : "app_dev.php/") . "test/session/$session_hash/start");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "panel_node_hash=" . urlencode($panel_node["hash"]) . "&panel_node_port=" . urlencode($panel_node_port) . ($values ? "&values=" . urlencode($values) : "") . "&client_ip=" . urlencode($client_ip) . "&client_browser=" . urlencode($client_browser) . "&debug=" . urlencode($debug ? 1 : 0));
            $result = json_decode(curl_exec($ch), true);
            curl_close($ch);
            return $result;
        } else {
            return $this->rRunnerService->startR($panel_node["hash"], $panel_node_port, $session_hash, $values, $client_ip, $client_browser, false, $debug ? 1 : 0);
        }
    }

    private function startListener($sock, $session_hash)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__);
        $response = "";

        $client_sock = @socket_accept($sock);
        if ($client_sock === false) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . ":socket_accept() failed, " . socket_strerror(socket_last_error($sock)) . ", $session_hash");
        } else {
            do {
                $buf = socket_read($client_sock, 8388608, PHP_NORMAL_READ);
                if ($buf === false) {
                    $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . ":socket_read() failed, " . socket_strerror(socket_last_error($client_sock)) . ", $session_hash");
                    break;
                }
                $buf = trim($buf);
                if (!$buf) {
                    continue;
                }

                $response .= $buf;
                break;
            } while (usleep(100 * 1000) || true);
            socket_close($client_sock);
        }

        socket_close($sock);

        return json_decode($response, true);
    }

    public function logError($session_hash, $calling_node_ip, $error, $type)
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

        $test_node = $this->loadBalancerService->getTestNodeById($session->getTestNodeId());
        if (!$test_node) {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - test node " . $test_node["hash"] . " not found.");
            return $this->prepareResponse($session_hash, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_ERROR
            ));
        }

        $test_node = $this->loadBalancerService->authorizeTestNode($calling_node_ip, $test_node["hash"]);
        if (!$test_node) {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - test node " . $test_node["hash"] . "  $calling_node_ip authentication failed.");
            return $this->prepareResponse($session_hash, array(
                "source" => self::SOURCE_PANEL_NODE,
                "code" => self::RESPONSE_AUTHENTICATION_FAILED
            ));
        }

        //@TODO unify response
        $this->saveErrorLog($session, $error, $type);
        $response = array("result" => 0);
        return $response;
    }

}
