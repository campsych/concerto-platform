<?php

namespace Concerto\TestBundle\Service;

use Concerto\PanelBundle\Entity\TestSession;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

abstract class ASessionRunnerService
{
    const OS_WIN = 0;
    const OS_LINUX = 1;

    protected $logger;
    protected $testRunnerSettings;
    protected $root;
    protected $doctrine;

    public function __construct(LoggerInterface $logger, $testRunnerSettings, $root, RegistryInterface $doctrine)
    {
        $this->logger = $logger;
        $this->testRunnerSettings = $testRunnerSettings;
        $this->root = $root;
        $this->doctrine = $doctrine;
    }

    abstract public function startNew(TestSession $session, $params, $client_ip, $client_browser, $debug = false);

    abstract public function submit(TestSession $session, $values, $client_ip, $client_browser);

    abstract public function backgroundWorker(TestSession $session, $values, $client_ip, $client_browser);

    abstract public function keepAlive(TestSession $session, $client_ip, $client_browser);

    abstract public function kill(TestSession $session, $client_ip, $client_browser);

    public function getSerializedConnection()
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
        if (array_key_exists("path", $params)) {
            $con_array["path"] = $params["path"];
        }
        if (array_key_exists("unix_socket", $params)) {
            $con_array["unix_socket"] = $params["unix_socket"];
        }
        $json_connection = json_encode($con_array);
        return $json_connection;
    }

    public function getRDir()
    {
        return realpath($this->root . "/../src/Concerto/TestBundle/Resources/R");
    }

    public function getOutputFilePath($session_hash)
    {
        return $this->getWorkingDirPath($session_hash) . "concerto.log";
    }

    public function getROutputFilePath($session_hash)
    {
        return $this->getOutputFilePath($session_hash) . ".r";
    }

    public function getPublicDirPath()
    {
        return $this->root . "/../src/Concerto/PanelBundle/Resources/public/files/";
    }

    public function getMediaUrl()
    {
        return $this->testRunnerSettings["dir"] . "bundles/concertopanel/files/";
    }

    public function getWorkingDirPath($session_hash, $create = true)
    {
        $path = $this->root . "/../src/Concerto/TestBundle/Resources/sessions/$session_hash/";
        if ($create && !file_exists($path)) {
            mkdir($path, 0755, true);
        }
        return $path;
    }

    //@TODO proper OS detection
    public function getOS()
    {
        if (strpos(strtolower(PHP_OS), "win") !== false) {
            return self::OS_WIN;
        } else {
            return self::OS_LINUX;
        }
    }

    public function escapeWindowsArg($arg)
    {
        $arg = addcslashes($arg, '"');
        $arg = str_replace("(", "^(", $arg);
        $arg = str_replace(")", "^)", $arg);
        return $arg;
    }

    public function getFifoDir()
    {
        return $this->getRDir() . "/fifo";
    }
}