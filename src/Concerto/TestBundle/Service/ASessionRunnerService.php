<?php

namespace Concerto\TestBundle\Service;

use Concerto\PanelBundle\Entity\TestSession;
use Concerto\PanelBundle\Repository\TestSessionRepository;
use Concerto\PanelBundle\Service\AdministrationService;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Concerto\PanelBundle\Service\TestSessionService;
use Symfony\Component\Process\Process;

abstract class ASessionRunnerService
{
    const WRITER_TIMEOUT = 30;

    protected $logger;
    protected $testRunnerSettings;
    protected $projectDir;
    protected $doctrine;
    protected $testSessionCountService;
    protected $administrationService;
    protected $testSessionRepository;
    protected $runnerType = -1;
    protected $environment;

    public function __construct($environment, LoggerInterface $logger, $testRunnerSettings, $projectDir, RegistryInterface $doctrine, TestSessionCountService $testSessionCountService, AdministrationService $administrationService, TestSessionRepository $testSessionRepository)
    {
        $this->logger = $logger;
        $this->testRunnerSettings = $testRunnerSettings;
        $this->projectDir = $projectDir;
        $this->doctrine = $doctrine;
        $this->testSessionCountService = $testSessionCountService;
        $this->administrationService = $administrationService;
        $this->testSessionRepository = $testSessionRepository;
        $this->environment = $environment;
    }

    abstract public function startNew(TestSession $session, $params, $cookies, $headers, $client_ip, $client_browser, $debug = false, $max_exec_time = null);

    abstract public function resume(TestSession $session, $cookies, $client_ip, $client_browser, $max_exec_time = null);

    abstract public function submit(TestSession $session, $values, $cookies, $client_ip, $client_browser);

    abstract public function backgroundWorker(TestSession $session, $values, $cookies, $client_ip, $client_browser);

    abstract public function keepAlive(TestSession $session, $client_ip, $client_browser);

    abstract public function kill(TestSession $session, $client_ip, $client_browser);

    abstract public function healthCheck();

    public function getDbConnectionParams()
    {
        $con = $this->doctrine->getConnection($this->testRunnerSettings["connection"]);
        $con_array = array(
            "driver" => $con->getDriver()->getName(),
            "host" => $con->getHost(),
            "port" => $con->getPort(),
            "dbname" => $con->getDatabase(),
            "username" => $con->getUsername(),
            "password" => $con->getPassword());

        //@TODO there should be no default port
        if (!$con_array["port"]) {
            $con_array["port"] = 3306;
        }
        $params = $con->getParams();
        if (isset($params["path"])) {
            $con_array["path"] = $params["path"];
        }
        if (isset($params["unix_socket"])) {
            $con_array["unix_socket"] = $params["unix_socket"];
        }
        return $con_array;
    }

    public function getRedisConnectionParams()
    {
        return [
            "host" => $this->testRunnerSettings["redis_host"],
            "port" => $this->testRunnerSettings["redis_port"],
            "password" => $this->testRunnerSettings["redis_pass"]
        ];
    }

    public function getRDir()
    {
        return realpath($this->projectDir . "/src/Concerto/TestBundle/Resources/R") . "/";
    }

    public function getROutputFilePath($session_hash)
    {
        if ($session_hash === null) return null;
        return realpath($this->projectDir . "/var/logs") . "/$session_hash.log";
    }

    public function getPublicDirPath()
    {
        return realpath($this->projectDir . "/src/Concerto/PanelBundle/Resources/public/files") . "/";
    }

    public function getPlatformUrl()
    {
        return rtrim($this->testRunnerSettings["platform_url"], "/");
    }

    public function getAppUrl()
    {
        $url = rtrim($this->testRunnerSettings["platform_url"], "/");
        if ($this->environment === "dev") $url .= "/app_dev.php";
        return $url;
    }

    public function getWorkingDirPath($session_hash, $create = true)
    {
        $path = null;
        if ($session_hash === null) {
            $path = $this->projectDir . "/src/Concerto/TestBundle/Resources/sessions/";
        } else {
            $path = $this->projectDir . "/src/Concerto/TestBundle/Resources/sessions/$session_hash/";
            if ($create && !file_exists($path)) {
                mkdir($path, 0755, true);
                mkdir($path . "files", 0755, true);
            }
        }
        return $path;
    }

    public function escapeWindowsArg($arg)
    {
        $arg = addcslashes($arg, '"');
        $arg = str_replace("(", "^(", $arg);
        $arg = str_replace(")", "^)", $arg);
        return $arg;
    }

    public function getSessionFifoDir()
    {
        return realpath($this->projectDir . "/var/R/session_fifo") . "/";
    }

    public function getServiceFifoDir()
    {
        return realpath($this->projectDir . "/var/R/service_fifo") . "/";
    }

    protected function checkSessionLimit($session, &$response)
    {
        $session_limit = $this->administrationService->getSessionLimit();
        $local_session_limit = $this->administrationService->getLocalSessionLimit();
        $total_limit_reached = $session_limit > 0 && $session_limit <= $this->testSessionCountService->getCurrentCount();
        $local_limit_reached = $local_session_limit > 0 && $local_session_limit <= $this->testSessionCountService->getCurrentLocalCount();
        if ($total_limit_reached || $local_limit_reached) {
            $session->setStatus(TestSessionService::STATUS_REJECTED);
            $this->testSessionRepository->save($session);
            $this->administrationService->insertSessionLimitMessage($session);
            $response = array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_SESSION_LIMIT_REACHED
            );
            return false;
        }
        return true;
    }

    protected function appendDebugDataToResponse(TestSession $session, $response, $offset = 0)
    {
        if (!$session->isDebug()) return $response;
        $out_path = $this->getROutputFilePath($session->getHash());
        if ($out_path !== null && file_exists($out_path)) {
            $new_data = file_get_contents($out_path, false, null, $offset);
            $response["debug"] = mb_convert_encoding($new_data, "UTF-8");
        }
        return $response;
    }

    protected function getDebugDataOffset(TestSession $session)
    {
        if (!$session->isDebug()) return 0;
        $out_path = $this->getROutputFilePath($session->getHash());
        if ($out_path !== null && file_exists($out_path)) {
            return filesize($out_path);
        }
        return 0;
    }

    protected function saveSubmitterPortFile($session_hash, $port)
    {
        $startTime = time();

        while (($fh = @fopen($this->getSubmitterPortFilePath($session_hash), "x")) === false) {
            if (time() - $startTime > self::WRITER_TIMEOUT) {
                $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - submitter port file timeout");
                return false;
            }
            usleep(100 * 1000);
        }
        fwrite($fh, $port . "\n");
        fclose($fh);
        return true;
    }

    protected function getSubmitterPortFilePath($session_hash)
    {
        return $this->getWorkingDirPath($session_hash) . "/submitter.port";
    }

    protected function isProcessReady($session_hash)
    {
        return !file_exists($this->getSubmitterPortFilePath($session_hash));
    }

    protected function waitForProcessReady($session_hash)
    {
        $startTime = time();
        while (!$this->isProcessReady($session_hash)) {
            if (time() - $startTime > self::WRITER_TIMEOUT) {
                return false;
            }
            usleep(100 * 1000);
        }
        return true;
    }

    protected function createSubmitterSock($session, $save_file, &$submitter_sock, &$error_response)
    {
        $submitter_sock = $this->createListenerSocket();
        if ($submitter_sock === false) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - creating listener socket for submitter session failed");
            $error_response = array(
                "source" => TestSessionService::SOURCE_TEST_NODE,
                "code" => TestSessionService::RESPONSE_ERROR
            );
            return false;
        }

        socket_getsockname($submitter_sock, $submitter_ip, $submitter_port);

        if ($session) {
            if ($save_file) {
                if ($this->saveSubmitterPortFile($session->getHash(), $submitter_port) === false) {
                    $error_response = array(
                        "source" => TestSessionService::SOURCE_TEST_NODE,
                        "code" => TestSessionService::RESPONSE_ERROR
                    );
                    return false;
                }
            }

            $session->setSubmitterPort($submitter_port);
            $this->testSessionRepository->save($session);
        }
        return $submitter_port;
    }

    protected function createListenerSocket($port = 0)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__);

        if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - socket_create() failed, listener socket, " . socket_strerror(socket_last_error()));
            return false;
        }
        if (socket_bind($sock, "0.0.0.0", $port) === false) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - socket_bind() failed, listener socket, " . socket_strerror(socket_last_error($sock)));
            socket_close($sock);
            return false;
        }
        if (socket_listen($sock, SOMAXCONN) === false) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - socket_listen() failed, listener socket, " . socket_strerror(socket_last_error($sock)));
            socket_close($sock);
            return false;
        }
        socket_set_nonblock($sock);
        return $sock;
    }

    protected function startListenerSocket($server_sock, $max_exec_time = NULL)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__);

        $startTime = time();
        $timeLimit = $max_exec_time;
        if ($timeLimit === null) {
            $timeLimit = $this->testRunnerSettings["max_execution_time"];
        }
        if ($timeLimit > 0) {
            $timeLimit += 5;
        }
        do {
            if ($timeLimit > 0 && time() - $startTime > $timeLimit) {
                $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - start listener timeout");
                return false;
            }
            if (($client_sock = @socket_accept($server_sock)) === false) {
                continue;
            }

            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - socket accepted");

            if (false === ($buf = socket_read($client_sock, 8388608, PHP_NORMAL_READ))) {
                continue;
            }
            if (!$msg = trim($buf)) {
                continue;
            }

            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $msg");
            return $msg;
        } while (usleep(100 * 1000) || true);
    }

    protected function writeToProcess($submitter_sock, $response)
    {
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__);

        $startTime = time();
        do {
            if (($client_sock = socket_accept($submitter_sock)) === false) {
                if (time() - $startTime > self::WRITER_TIMEOUT) {
                    $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - writing to process timeout");
                    return false;
                }
                continue;
            }

            $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - socket accepted");

            $buffer = json_encode($response) . "\n";
            $sent = socket_write($client_sock, $buffer);
            if ($sent === false) {
                $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - writing to process failed");
                return false;
            }
            if ($sent != strlen($buffer)) {
                $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - writing to process failed (length)");
                return false;
            }
            break;
        } while (usleep(100 * 1000) || true);
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - submitter ended");
        return true;
    }

    protected function getCommand($session_hash)
    {
        $rscript_exec = $this->testRunnerSettings["rscript_exec"];
        $ini_path = $this->getRDir() . "standalone.R";
        $rout = $this->getROutputFilePath($session_hash);
        $sessionLogLevel = (int)$this->administrationService->getSettingValue("session_log_level");

        $cmd = null;
        switch (AdministrationService::getOS()) {
            case AdministrationService::OS_LINUX:
                $cmd = "nohup $rscript_exec --no-save --no-restore --quiet '$ini_path' ";
                if ($sessionLogLevel > 0) {
                    $cmd .= ($rout ? ("> '" . $rout . "' 2>&1 ") : "");
                }
                $cmd .= "& echo $!";
                break;
            default:
                $cmd = "start cmd /C \"\"{$this->escapeWindowsArg($rscript_exec)}\" --no-save --no-restore --quiet \"{$this->escapeWindowsArg($ini_path)}\" ";
                if ($sessionLogLevel > 0) {
                    $cmd .= ($rout ? ("> \"" . $this->escapeWindowsArg($rout) . "\" 2>&1\" ") : "");
                }
                break;
        }
        return $cmd;
    }

    protected function startChildProcess($client, $sessionHash, $request = null, $maxExecTime = null, $initialPort = null)
    {
        if ($maxExecTime === null) {
            $maxExecTime = $this->testRunnerSettings["max_execution_time"];
        }

        $workingDir = $this->getWorkingDirPath($sessionHash);
        $maxIdleTime = $this->administrationService->getSettingValueForSessionHash($sessionHash, "max_idle_time");
        $rout = $this->getROutputFilePath($sessionHash);

        $response = json_encode([
            "workingDir" => $workingDir,
            "maxExecTime" => $maxExecTime,
            "maxIdleTime" => $maxIdleTime,
            "client" => $client,
            "sessionId" => $sessionHash,
            "rLogPath" => $rout,
            "response" => $request,
            "initialPort" => $initialPort,
            "runnerType" => $this->runnerType
        ]);

        $path = $this->getSessionFifoDir() . ($sessionHash ? $sessionHash : uniqid("hc", true)) . ".fifo";
        posix_mkfifo($path, POSIX_S_IFIFO | 0644);

        $startTime = time();
        while ((@$fh = fopen($path, "wn")) === false) {
            if (time() - $startTime > 60) {
                $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - fopen() failed");
                return false;
            }
            usleep(100 * 1000);
        }

        stream_set_blocking($fh, 1);
        $buffer = $response . "\n";
        $sent = fwrite($fh, $buffer);
        $success = $sent !== false;
        if (!$success) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - fwrite() failed");
        }
        if (strlen($buffer) != $sent) {
            $this->logger->error(__CLASS__ . ":" . __FUNCTION__ . " - fwrite() failed, sent only $sent/" . strlen($buffer));
            $success = false;
        }
        fclose($fh);
        return $success;
    }

    protected function startStandaloneProcess($client, $sessionHash, $request = null, $maxExecTime = null, $initialPort = null)
    {
        $cmd = $this->getCommand($sessionHash);
        $this->logger->info(__CLASS__ . ":" . __FUNCTION__ . " - $cmd");

        $dbConnection = json_encode($this->getDbConnectionParams());
        $maxExecTime = $maxExecTime === null ? $this->testRunnerSettings["max_execution_time"] : $maxExecTime;
        $maxIdleTime = $this->administrationService->getSettingValueForSessionHash($sessionHash, "max_idle_time");
        $keepAliveToleranceTime = $this->testRunnerSettings["keep_alive_tolerance_time"];
        $workingDir = $this->getWorkingDirPath($sessionHash);
        $publicDir = $this->getPublicDirPath();
        $platformUrl = $this->getPlatformUrl();
        $appUrl = $this->getAppUrl();
        $client = json_encode($client);
        $request = json_encode($request ? $request : array());
        $sessionStorage = $this->testRunnerSettings["session_storage"];
        $redisConnection = json_encode($this->getRedisConnectionParams());
        $sessionFilesExpiration = $this->administrationService->getSettingValue("session_files_expiration");
        $serviceFifoPath = $this->getServiceFifoDir();

        $process = new Process($cmd);
        $process->setEnhanceWindowsCompatibility(false);
        $process->inheritEnvironmentVariables(true);

        $r_environ_path = $this->testRunnerSettings["r_environ_session_path"];
        $r_profile_path = $this->testRunnerSettings['r_profile_session_path'];
        $env = array(
            "CONCERTO_R_APP_URL" => $appUrl,
            "CONCERTO_R_CLIENT" => $client,
            "CONCERTO_R_DB_CONNECTION" => $dbConnection,
            "CONCERTO_R_MAX_EXEC_TIME" => $maxExecTime,
            "CONCERTO_R_MAX_IDLE_TIME" => $maxIdleTime,
            "CONCERTO_R_KEEP_ALIVE_TOLERANCE_TIME" => $keepAliveToleranceTime,
            "CONCERTO_R_PLATFORM_URL" => $platformUrl,
            "CONCERTO_R_INITIAL_PORT" => $initialPort,
            "CONCERTO_R_PUBLIC_DIR" => $publicDir,
            "CONCERTO_R_REDIS_CONNECTION" => $redisConnection,
            "CONCERTO_R_REQUEST" => $request,
            "CONCERTO_R_RUNNER_TYPE" => $this->runnerType,
            "CONCERTO_R_SESSION_HASH" => $sessionHash,
            "CONCERTO_R_SESSION_STORAGE" => $sessionStorage,
            "CONCERTO_R_WORKING_DIR" => $workingDir,
            "CONCERTO_R_SESSION_FILES_EXPIRATION" => $sessionFilesExpiration,
            "CONCERTO_R_SERVICE_FIFO_PATH" => $serviceFifoPath,
            "R_GC_MEM_GROW" => 0,
            "R_ENVIRON_USER" => $r_environ_path !== "null" ? $r_environ_path : "{$this->projectDir}/app/config/R/.Renviron_session",
            "R_PROFILE_USER" => $r_profile_path !== "null" ? $r_profile_path : "{$this->projectDir}/app/config/R/.Rprofile_session"
        );
        $process->setEnv($env);

        $process->mustRun();
        return true;
    }
}