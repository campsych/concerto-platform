<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Entity\TestSession;
use Concerto\PanelBundle\Repository\TestSessionRepository;
use Concerto\PanelBundle\Repository\TestSessionLogRepository;
use Concerto\PanelBundle\Repository\TestRepository;
use Psr\Log\LoggerInterface;
use Concerto\PanelBundle\Entity\TestSessionLog;
use Concerto\TestBundle\Service\RRunnerService;

class TestSessionService {

    const SOURCE_TEST_SERVER = 0;
    const SOURCE_PROCESS = 1;
    const RESPONSE_VIEW_TEMPLATE = 0;
    const RESPONSE_FINISHED = 1;
    const RESPONSE_SUBMIT = 2;
    const RESPONSE_SERIALIZE = 3;
    const RESPONSE_SERIALIZATION_FINISHED = 4;
    const RESPONSE_VIEW_FINAL_TEMPLATE = 5;
    const RESPONSE_VIEW_RESUME = 6;
    const RESPONSE_RESULTS = 7;
    const RESPONSE_AUTHENTICATION_FAILED = 8;
    const RESPONSE_STARTING = 9;
    const RESPONSE_KEEPALIVE_CHECKIN = 10;
    const RESPONSE_UNRESUMABLE = 11;
    const RESPONSE_ERROR = -1;
    const STATUS_RUNNING = 0;
    const STATUS_SERIALIZED = 1;
    const STATUS_FINALIZED = 2;
    const STATUS_ERROR = 3;

    private $testSessionRepository;
    private $testRepository;
    private $testSessionLogRepository;
    private $logger;
    private $nodes;
    private $environment;
    private $secret;
    private $rRunnerService;

    public function __construct($environment, TestSessionRepository $testSessionRepository, TestRepository $testRepository, TestSessionLogRepository $testSessionLogRepository, $nodes, $secret, LoggerInterface $logger, RRunnerService $rRunnerService) {
        $this->environment = $environment;
        $this->testSessionRepository = $testSessionRepository;
        $this->testRepository = $testRepository;
        $this->testSessionLogRepository = $testSessionLogRepository;
        $this->nodes = $nodes;
        $this->secret = $secret;
        $this->logger = $logger;
        $this->rRunnerService = $rRunnerService;
    }

    private function getTestServerNode() {
        return $this->nodes[0];
    }

    private function authenticateNode($node_ip, $node_hash) {
        foreach ($this->nodes as $node) {
            if ($node_hash == $node["hash"]) {
                return $node;
            }
        }
        return false;
    }

    private function generateSessionHash($session_id) {
        return sha1($this->secret . $session_id);
    }

    public function startNewSession($r_server_node_hash, $test_slug, $params, $client_ip, $client_browser, $calling_node_ip, $debug) {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $r_server_node_hash, $test_slug, $params, $client_ip, $client_browser, $calling_node_ip, $debug");

        $r_server_node = $this->authenticateNode($calling_node_ip, $r_server_node_hash);
        if ($debug || $r_server_node) {
            $test_server_node = $this->getTestServerNode();
            if (($test_server_node_sock = $this->createListenerSocket($test_server_node["ip"])) === false) {
                return false;
            }
            $test_server_node_host = "";
            $test_server_node_port = 0;
            socket_getsockname($test_server_node_sock, $test_server_node_host, $test_server_node_port);

            if ($debug) {
                $r_server_node = $this->getOptimalRServerNode();
            }

            $session = new TestSession();
            $session->setTest($this->testRepository->findOneBySlug($test_slug));
            $session->setClientIp($client_ip);
            $session->setClientBrowser($client_browser);
            $session->setTestServerNodeId($test_server_node["id"]);
            $session->setTestServerNodePort($test_server_node_port);
            $session->setRServerNodeId($r_server_node["id"]);
            $session->setDebug($debug);
            $session->setParams($this->validateParams($session, $params));
            $this->testSessionRepository->save($session);
            $session->setHash($this->generateSessionHash($session->getId()));
            $this->testSessionRepository->save($session);
            $this->testSessionRepository->clear();

            $this->initiateRServerNode($session->getHash(), $test_server_node, $test_server_node_port, $r_server_node, $client_ip, $client_browser, $debug);
            $response = $this->startListener($test_server_node_sock);

            return $this->prepareResponse($session->getHash(), $response);
        } else {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - NODE $r_server_node_hash / $calling_node_ip AUTHENTICATION FAILED!");
            return json_encode(array(
                "source" => self::SOURCE_TEST_SERVER,
                "code" => self::RESPONSE_AUTHENTICATION_FAILED
            ));
        }
    }

    private function validateParams($session, $params) {
        $dp = json_decode($params, true);
        $result = array();
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

    public function submit($r_server_node_hash, $session_hash, $values, $client_ip, $client_browser, $calling_node_ip) {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $r_server_node_hash, $session_hash, $values, $client_ip, $client_browser, $calling_node_ip");

        $r_server_node = $this->authenticateNode($calling_node_ip, $r_server_node_hash);
        if ($r_server_node) {
            $session = $this->testSessionRepository->findOneBy(array("hash" => $session_hash));
            $test_server_node = $this->getTestServerNode();
            if ($session !== null) {
                if (($client_sock = $this->createListenerSocket($test_server_node["ip"])) === false) {
                    return false;
                }
                socket_getsockname($client_sock, $test_server_node_ip, $test_server_node_port);

                $r_server_node_port = $session->getRServerNodePort();

                $session->setTestServerNodePort($test_server_node_port);
                $session->setClientIp($client_ip);
                $session->setClientBrowser($client_browser);
                $session->setUpdated();
                $this->testSessionRepository->save($session);
                $this->testSessionRepository->clear();

                if ($session->getStatus() === self::STATUS_SERIALIZED) {
                    $this->initiateRServerNode($session_hash, $test_server_node, $test_server_node_port, $r_server_node, $client_ip, $client_browser, $session->isDebug(), $values);
                } else {
                    $this->submitToRServer($r_server_node, $r_server_node_port, $test_server_node, $test_server_node_port, $client_ip, $client_browser, $values);
                }
                $response = $this->startListener($client_sock);
                return $this->prepareResponse($session_hash, $response);
            } else {
                $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - SESSION $session_hash NOT FOUND!");
                return json_encode(array(
                    "source" => self::SOURCE_TEST_SERVER,
                    "code" => self::RESPONSE_ERROR
                ));
            }
        } else {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - NODE $r_server_node_hash  $calling_node_ip AUTHENTICATION FAILED!");
            return json_encode(array(
                "source" => self::SOURCE_TEST_SERVER,
                "code" => self::RESPONSE_AUTHENTICATION_FAILED
            ));
        }
    }

    public function keepAlive($r_server_node_hash, $session_hash, $client_ip, $calling_node_ip) {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $r_server_node_hash, $session_hash, $client_ip, $calling_node_ip");

        $r_server_node = $this->authenticateNode($calling_node_ip, $r_server_node_hash);
        if ($r_server_node) {
            $this->testSessionRepository->clear();
            $session = $this->testSessionRepository->findOneBy(array("hash" => $session_hash));
            $test_server_node = $this->getTestServerNode();
            if ($session !== null) {
                $r_server_node_port = $session->getRServerNodePort();

                if ($session->getStatus() !== self::STATUS_SERIALIZED) {
                    $this->keepAliveRServer($r_server_node, $r_server_node_port, $test_server_node, $client_ip);
                }
                return $this->prepareResponse($session_hash, json_encode(array(
                            "source" => self::SOURCE_TEST_SERVER,
                            "code" => self::RESPONSE_KEEPALIVE_CHECKIN
                )));
            } else {
                $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - SESSION $session_hash NOT FOUND!");
                return json_encode(array(
                    "source" => self::SOURCE_TEST_SERVER,
                    "code" => self::RESPONSE_ERROR
                ));
            }
        } else {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - NODE $r_server_node_hash  $calling_node_ip AUTHENTICATION FAILED!");
            return json_encode(array(
                "source" => self::SOURCE_TEST_SERVER,
                "code" => self::RESPONSE_AUTHENTICATION_FAILED
            ));
        }
    }

    public function resume($r_server_node_hash, $session_hash, $calling_node_ip) {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $r_server_node_hash, $session_hash, $calling_node_ip");

        $r_server_node = $this->authenticateNode($calling_node_ip, $r_server_node_hash);
        if ($r_server_node) {
            $session = $this->testSessionRepository->findOneBy(array("hash" => $session_hash));
            if ($session !== null) {
                $response = json_encode(array(
                    "source" => self::SOURCE_TEST_SERVER,
                    "code" => $session->getStatus() == self::STATUS_FINALIZED ? self::RESPONSE_VIEW_FINAL_TEMPLATE : self::RESPONSE_VIEW_TEMPLATE
                ));
                return $this->prepareResponse($session_hash, $response);
            } else {
                $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - SESSION $session_hash NOT FOUND!");
                $response = json_encode(array(
                    "source" => self::SOURCE_TEST_SERVER,
                    "code" => self::RESPONSE_ERROR
                ));
                return $response;
            }
        } else {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - NODE $r_server_node_hash / $calling_node_ip AUTHENTICATION FAILED!");
            return json_encode(array(
                "source" => self::SOURCE_TEST_SERVER,
                "code" => self::RESPONSE_AUTHENTICATION_FAILED
            ));
        }
    }

    public function results($r_server_node_hash, $session_hash, $calling_node_ip) {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $r_server_node_hash, $session_hash, $calling_node_ip");
        $r_server_node = $this->authenticateNode($calling_node_ip, $r_server_node_hash);
        if ($r_server_node) {
            $session = $this->testSessionRepository->findOneBy(array("hash" => $session_hash));
            if ($session !== null) {
                $response = json_encode(array(
                    "source" => self::SOURCE_TEST_SERVER,
                    "code" => self::RESPONSE_RESULTS
                ));
                return $this->prepareResponse($session_hash, $response);
            } else {
                $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - SESSION $session_hash NOT FOUND!");
                $response = json_encode(array(
                    "source" => self::SOURCE_TEST_SERVER,
                    "code" => self::RESPONSE_ERROR
                ));
                return $response;
            }
        } else {
            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - NODE $r_server_node_hash / $calling_node_ip AUTHENTICATION FAILED!");
            return json_encode(array(
                "source" => self::SOURCE_TEST_SERVER,
                "code" => self::RESPONSE_AUTHENTICATION_FAILED
            ));
        }
    }

    private function saveRErrorLog(TestSession $session) {
        $log = new TestSessionLog();
        $log->setBrowser($session->getClientBrowser());
        $log->setIp($session->getClientIp());
        $log->setMessage($session->getError());
        $log->setType(TestSessionLog::TYPE_R);
        $log->setTest($session->getTest());
        $this->testSessionLogRepository->save($log);
    }

    private function prepareResponse($session_hash, $response) {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash");
        $session = $this->testSessionRepository->findOneBy(array("hash" => $session_hash));
        $decoded_response = json_decode($response, true);
        if ($session !== null) {

            if ($decoded_response["code"] === self::RESPONSE_ERROR) {
                $this->saveRErrorLog($session);
            }

            switch ($decoded_response["code"]) {
                case self::RESPONSE_KEEPALIVE_CHECKIN:
                    break;
                case self::RESPONSE_RESULTS:
                    $decoded_response["results"] = $session->getReturns();
                    break;
                case self::RESPONSE_VIEW_RESUME:
                case self::RESPONSE_VIEW_FINAL_TEMPLATE:
                case self::RESPONSE_VIEW_TEMPLATE:
                    $decoded_response["hash"] = $session_hash;
                    $decoded_response["results"] = $session->getReturns();
                    $decoded_response["timeLimit"] = $session->getTimeLimit();
                    $decoded_response["templateHead"] = $session->getTemplateHead();
                    $decoded_response["templateHtml"] = $session->getTemplateHtml();
                    $decoded_response["loaderHead"] = $session->getLoaderHead();
                    $decoded_response["loaderHtml"] = $session->getLoaderHtml();
                    $decoded_response["isResumable"] = $session->getTest()->isResumable();
                    break;
            }
            if (!$session->isDebug()) {
                $decoded_response["debug"] = "";
            }
        }
        return json_encode($decoded_response);
    }

    private function submitToRServer($r_server_node, $r_server_node_port, $test_server_node, $test_server_node_port, $client_ip, $client_browser, $values) {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - " . json_encode($r_server_node) . ", $r_server_node_port, " . json_encode($test_server_node) . ", $test_server_node_port, $client_ip, $client_browser, $values");
        if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
            return false;
        }
        if (socket_connect($sock, $r_server_node["ip"], $r_server_node_port) === false) {
            return false;
        }
        socket_write($sock, json_encode(array(
                    "source" => self::SOURCE_TEST_SERVER,
                    "code" => self::RESPONSE_SUBMIT,
                    "testServer" => array("ip" => $test_server_node["ip"], "port" => $test_server_node_port, "client_ip" => $client_ip, "client_browser" => $client_browser),
                    "values" => $values
                )) . "\n");
        socket_close($sock);
    }

    private function keepAliveRServer($r_server_node, $r_server_node_port, $test_server_node, $client_ip) {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - " . json_encode($r_server_node) . ", $r_server_node_port, " . json_encode($test_server_node) . ", $client_ip");
        if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
            return false;
        }
        if (socket_connect($sock, $r_server_node["ip"], $r_server_node_port) === false) {
            return false;
        }
        socket_write($sock, json_encode(array(
                    "source" => self::SOURCE_TEST_SERVER,
                    "code" => self::RESPONSE_KEEPALIVE_CHECKIN,
                    "testServer" => array("ip" => $test_server_node["ip"], "client_ip" => $client_ip)
                )) . "\n");
        socket_close($sock);
    }

    private function createListenerSocket($host) {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $host");
        if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . ":socket_create() failed: reason: " . socket_strerror(socket_last_error()));
            return false;
        }
        if (socket_bind($sock, $host) === false) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . ":socket_bind() failed: reason: " . socket_strerror(socket_last_error($sock)));
            return false;
        }
        if (socket_listen($sock, SOMAXCONN) === false) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . ":socket_listen() failed: reason: " . socket_strerror(socket_last_error($sock)));
            return false;
        }
        return $sock;
    }

    private function getOptimalRServerNode() {
        return $this->nodes[0];
    }

    private function initiateRServerNode($session_hash, $test_server_node, $test_server_node_port, $r_server_node, $client_ip, $client_browser, $debug, $values = null) {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, " . json_encode($test_server_node) . ", " . json_encode($r_server_node) . ", $client_ip, $client_browser, $debug, $values");

        if ($r_server_node["id"] != "local") {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $r_server_node["protocol"] . "://" . $r_server_node["host"] . $r_server_node["dir"] . ($this->environment == "prod" ? "" : "app_dev.php/") . "test/session/$session_hash/start");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "test_server_node_hash=" . urlencode($test_server_node["hash"]) . "&test_server_node_port=" . urlencode($test_server_node_port) . ($values ? "&values=" . urlencode($values) : "") . "&client_ip=" . urlencode($client_ip) . "&client_browser=" . urlencode($client_browser) . "&debug=" . urlencode($debug ? 1 : 0));
            //curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_exec($ch);
            curl_close($ch);
        } else {
            $this->rRunnerService->startR($test_server_node["hash"], $test_server_node_port, $session_hash, $values, $client_ip, $client_browser, false, $debug ? 1 : 0);
        }
    }

    private function startListener($sock) {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__);
        $response = "";
        do {
            $client_sock = @socket_accept($sock);
            if ($client_sock === false) {
                $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . ":socket_accept() failed: reason: " . socket_strerror(socket_last_error($sock)));
                break;
            }
            do {
                $buf = socket_read($client_sock, 8388608, PHP_NORMAL_READ);
                if ($buf === false) {
                    $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . ":socket_read() failed: reason: " . socket_strerror(socket_last_error($client_sock)));
                    break 2;
                }
                $buf = trim($buf);
                $this->logger->info($buf);
                if (!$buf) {
                    usleep(10000);
                    continue;
                }
                $response .= $buf;
                socket_close($client_sock);
                break 2;
            } while (true);
            socket_close($client_sock);
            usleep(10000);
        } while (true);
        socket_close($sock);
        return $response;
    }

}
