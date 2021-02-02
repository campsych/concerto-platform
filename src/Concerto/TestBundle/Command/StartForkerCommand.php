<?php

namespace Concerto\TestBundle\Command;

use Concerto\PanelBundle\Service\AdministrationService;
use Concerto\TestBundle\Service\ASessionRunnerService;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class StartForkerCommand extends Command
{

    private $testRunnerSettings;
    private $doctrine;
    private $sessionRunnerService;
    private $projectDir;
    private $administrationService;

    public function __construct($testRunnerSettings, RegistryInterface $doctrine, ASessionRunnerService $sessionRunnerService, $projectDir, AdministrationService $administrationService)
    {
        parent::__construct();

        $this->doctrine = $doctrine;
        $this->testRunnerSettings = $testRunnerSettings;
        $this->sessionRunnerService = $sessionRunnerService;
        $this->projectDir = $projectDir;
        $this->administrationService = $administrationService;
    }

    protected function configure()
    {
        $this->setName("concerto:forker:start")->setDescription("Start forker process.");
    }

    private function getCommand()
    {
        $forkerPath = "{$this->projectDir}/src/Concerto/TestBundle/Resources/R/forker.R";
        $logPath = "{$this->projectDir}/var/logs/forker.log";

        return "nohup {$this->testRunnerSettings["rscript_exec"]} --no-save --no-restore --quiet '$forkerPath' >> '$logPath' 2>&1 & echo $!";
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("starting forker...");

        $sessionFifoPath = $this->sessionRunnerService->getSessionFifoDir();
        $serviceFifoPath = $this->sessionRunnerService->getServiceFifoDir();
        $publicDir = $this->sessionRunnerService->getPublicDirPath();
        $dbConnection = json_encode($this->sessionRunnerService->getDbConnectionParams());
        $platformUrl = $this->sessionRunnerService->getPlatformUrl();
        $appUrl = $this->sessionRunnerService->getAppUrl();
        $maxExecTime = $this->testRunnerSettings["max_execution_time"];
        $maxIdleTime = $this->testRunnerSettings["max_idle_time"];
        $keepAliveToleranceTime = $this->testRunnerSettings["keep_alive_tolerance_time"];
        $sessionStorage = $this->testRunnerSettings["session_storage"];
        $redisConnection = json_encode($this->sessionRunnerService->getRedisConnectionParams());
        $sessionFilesExpiration = $this->administrationService->getSettingValue("session_files_expiration");
        $sessionLogLevel = $this->administrationService->getSettingValue("session_log_level");
        $forcedGcInterval = $this->testRunnerSettings["r_forced_gc_interval"];

        $cmd = $this->getCommand();
        $process = new Process($cmd);
        $process->inheritEnvironmentVariables(true);

        $r_environ_path = $this->testRunnerSettings["r_environ_session_path"];
        $r_profile_path = $this->testRunnerSettings['r_profile_session_path'];
        $env = [
            "CONCERTO_R_APP_URL" => $appUrl,
            "CONCERTO_R_DB_CONNECTION" => $dbConnection,
            "CONCERTO_R_SESSION_FIFO_PATH" => $sessionFifoPath,
            "CONCERTO_R_SERVICE_FIFO_PATH" => $serviceFifoPath,
            "CONCERTO_R_MAX_EXEC_TIME" => $maxExecTime,
            "CONCERTO_R_MAX_IDLE_TIME" => $maxIdleTime,
            "CONCERTO_R_KEEP_ALIVE_TOLERANCE_TIME" => $keepAliveToleranceTime,
            "CONCERTO_R_PLATFORM_URL" => $platformUrl,
            "CONCERTO_R_PUBLIC_DIR" => $publicDir,
            "CONCERTO_R_REDIS_CONNECTION" => $redisConnection,
            "CONCERTO_R_SESSION_STORAGE" => $sessionStorage,
            "CONCERTO_R_SESSION_FILES_EXPIRATION" => $sessionFilesExpiration,
            "CONCERTO_R_SESSION_LOG_LEVEL" => $sessionLogLevel,
            "R_ENVIRON_USER" => $r_environ_path !== "null" ? $r_environ_path : "{$this->projectDir}/app/config/R/.Renviron_session",
            "R_PROFILE_USER" => $r_profile_path !== "null" ? $r_profile_path : "{$this->projectDir}/app/config/R/.Rprofile_session",
            "CONCERTO_R_FORCED_GC_INTERVAL" => $forcedGcInterval,
            "R_GC_MEM_GROW" => 0
        ];
        $process->setEnv($env);

        $process->mustRun();
        if ($process->getExitCode() == 0) {
            $output->writeln("forker started");
        } else {
            $output->writeln("something went wrong: non zero exit code");
        }
    }

}
