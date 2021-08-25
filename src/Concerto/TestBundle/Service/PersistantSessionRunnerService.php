<?php

namespace Concerto\TestBundle\Service;

use Concerto\PanelBundle\Entity\TestSession;
use Concerto\PanelBundle\Repository\TestSessionRepository;
use Concerto\PanelBundle\Service\AdministrationService;
use Concerto\PanelBundle\Service\TestSessionService;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Process\Process;

class PersistantSessionRunnerService extends ASessionRunnerService
{
    public function __construct($environment, LoggerInterface $logger, TestSessionRepository $testSessionRepository, AdministrationService $administrationService, TestSessionCountService $testSessionCountService, RegistryInterface $doctrine, $testRunnerSettings, $projectDir)
    {
        $this->runnerType = 0;

        parent::__construct($environment, $logger, $testRunnerSettings, $projectDir, $doctrine, $testSessionCountService, $administrationService, $testSessionRepository);
    }

    public function healthCheck()
    {
        $workingDirPath = $this->getWorkingDirPath(null);
        if (!is_writeable($workingDirPath)) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - working dir path $workingDirPath not writeable");
            return false;
        }

        $port = $this->createSubmitterSock(null, false, $submitter_sock, $error_response);
        if ($port === false) {
            return false;
        }

        $client = array();

        if (AdministrationService::getOS() == AdministrationService::OS_LINUX && $this->testRunnerSettings["session_forking"] == "true") {
            $success = $this->startChildProcess($client, null, null, null, $port);
        } else {
            $success = $this->startStandaloneProcess($client, null, null, null, $port);
        }
        if (!$success) {
            socket_close($submitter_sock);
            return false;
        }

        $response = $this->startListenerSocket($submitter_sock);
        socket_close($submitter_sock);
        if ($response === false) {
            return false;
        }
        $response = json_decode($response, true);

        return $response["code"] === 1;
    }

    public function startNew(TestSession $session, $params, $cookies, $headers, $client_ip, $client_browser, $debug = false, $max_exec_time = null)
    {
        $session_hash = $session->getHash();
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $params, $client_ip, $client_ip, $client_browser, $debug");

        if (!$this->checkSessionLimit($session, $response)) return $response;

        $client = array(
            "ip" => $client_ip,
            "browser" => $client_browser
        );

        if (!$this->createSubmitterSock($session, false, $submitter_sock, $error_response)) return $error_response;

        $request = array(
            "source" => TestSessionService::SOURCE_PANEL_NODE,
            "code" => TestSessionService::RESPONSE_STARTING,
            "client" => $client,
            "cookies" => $cookies,
            "headers" => $headers
        );

        $success = false;
        if (AdministrationService::getOS() == AdministrationService::OS_LINUX && $this->testRunnerSettings["session_forking"] == "true") {
            $success = $this->startChildProcess($client, $session_hash, $request, $max_exec_time);
        } else {
            $success = $this->startStandaloneProcess($client, $session_hash, $request, $max_exec_time);
        }
        if (!$success) {
            socket_close($submitter_sock);
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - creating R process failed");
            return array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_ERROR
            );
        }

        $response = $this->startListenerSocket($submitter_sock, $max_exec_time);
        socket_close($submitter_sock);
        if ($response === false) {
            return array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_ERROR
            );
        }
        $response = json_decode($response, true);
        $response = $this->appendDebugDataToResponse($session, $response);

        $this->testSessionRepository->clear();
        return $response;
    }

    public function resume(TestSession $session, $cookies, $client_ip, $client_browser, $max_exec_time = null)
    {
        $session_hash = $session->getHash();
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $client_ip, $client_browser");

        $client = array(
            "ip" => $client_ip,
            "browser" => $client_browser
        );

        if (!$this->createSubmitterSock($session, true, $submitter_sock, $error_response)) return $error_response;
        $debugOffset = $this->getDebugDataOffset($session);

        $sent = $this->writeToProcess($submitter_sock, array(
            "source" => TestSessionService::SOURCE_PANEL_NODE,
            "code" => TestSessionService::RESPONSE_RESUME,
            "client" => $client,
            "cookies" => $cookies
        ));
        if ($sent === false) {
            socket_close($submitter_sock);
            return $response = array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_SESSION_LOST
            );
        }

        $response = $this->startListenerSocket($submitter_sock);
        socket_close($submitter_sock);
        if ($response === false) {
            return array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_ERROR
            );
        }
        $response = json_decode($response, true);
        $response = $this->appendDebugDataToResponse($session, $response, $debugOffset);

        $this->testSessionRepository->clear();
        return $response;
    }

    public function submit(TestSession $session, $values, $cookies, $client_ip, $client_browser)
    {
        $session_hash = $session->getHash();
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $client_ip, $client_browser");

        $client = array(
            "ip" => $client_ip,
            "browser" => $client_browser
        );

        if (!$this->createSubmitterSock($session, true, $submitter_sock, $error_response)) return $error_response;
        $debugOffset = $this->getDebugDataOffset($session);

        $sent = $this->writeToProcess($submitter_sock, array(
            "source" => TestSessionService::SOURCE_PANEL_NODE,
            "code" => TestSessionService::RESPONSE_SUBMIT,
            "client" => $client,
            "values" => $values,
            "cookies" => $cookies
        ));
        if ($sent === false) {
            socket_close($submitter_sock);
            return $response = array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_SESSION_LOST
            );
        }

        $response = $this->startListenerSocket($submitter_sock);
        socket_close($submitter_sock);
        if ($response === false) {
            return array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_ERROR
            );
        }
        $response = json_decode($response, true);
        $response = $this->appendDebugDataToResponse($session, $response, $debugOffset);

        $this->testSessionRepository->clear();
        return $response;
    }

    public function backgroundWorker(TestSession $session, $values, $cookies, $client_ip, $client_browser)
    {
        $session_hash = $session->getHash();
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $client_ip, $client_browser");

        $client = array(
            "ip" => $client_ip,
            "browser" => $client_browser
        );

        if (!$this->createSubmitterSock($session, true, $submitter_sock, $error_response)) return $error_response;
        $debugOffset = $this->getDebugDataOffset($session);

        $sent = $this->writeToProcess($submitter_sock, array(
            "source" => TestSessionService::SOURCE_PANEL_NODE,
            "code" => TestSessionService::RESPONSE_WORKER,
            "client" => $client,
            "values" => $values,
            "cookies" => $cookies
        ));
        if ($sent === false) {
            socket_close($submitter_sock);
            return $response = array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_SESSION_LOST
            );
        }

        $response = $this->startListenerSocket($submitter_sock);
        socket_close($submitter_sock);
        if ($response === false) {
            return array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_ERROR
            );
        }
        $response = json_decode($response, true);
        $response = $this->appendDebugDataToResponse($session, $response, $debugOffset);

        $this->testSessionRepository->clear();
        return $response;
    }

    public function keepAlive(TestSession $session, $client_ip, $client_browser)
    {
        $session_hash = $session->getHash();
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $client_ip, $client_browser");

        $client = array(
            "ip" => $client_ip,
            "browser" => $client_browser
        );

        if (!$this->createSubmitterSock($session, true, $submitter_sock, $error_response)) return $error_response;

        $sent = $this->writeToProcess($submitter_sock, array(
            "source" => TestSessionService::SOURCE_PANEL_NODE,
            "code" => TestSessionService::RESPONSE_KEEPALIVE_CHECKIN,
            "client" => $client
        ));
        socket_close($submitter_sock);
        if ($sent === false) {
            return $response = array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_SESSION_LOST
            );
        }

        $this->testSessionRepository->clear();
        return array(
            "source" => TestSessionService::SOURCE_PROCESS,
            "code" => TestSessionService::RESPONSE_KEEPALIVE_CHECKIN
        );
    }

    public function kill(TestSession $session, $client_ip, $client_browser)
    {
        $session_hash = $session->getHash();
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $session_hash, $client_ip, $client_browser");

        $client = array(
            "ip" => $client_ip,
            "browser" => $client_browser
        );

        if (!$this->createSubmitterSock($session, true, $submitter_sock, $error_response)) return $error_response;

        $sent = $this->writeToProcess($submitter_sock, array(
            "source" => TestSessionService::SOURCE_PANEL_NODE,
            "code" => TestSessionService::RESPONSE_STOP,
            "client" => $client
        ));
        socket_close($submitter_sock);
        if ($sent === false) {
            return $response = array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_SESSION_LOST
            );
        }

        $this->testSessionRepository->clear();
        return array(
            "source" => TestSessionService::SOURCE_PROCESS,
            "code" => TestSessionService::RESPONSE_STOPPED
        );
    }
}