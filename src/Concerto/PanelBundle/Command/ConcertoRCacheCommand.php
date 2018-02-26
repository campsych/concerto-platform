<?php

namespace Concerto\PanelBundle\Command;

use Concerto\PanelBundle\Service\RDataCacheService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\DomCrawler\Crawler;

class ConcertoRCacheCommand extends Command
{
    private $cacheService;
    private $kernel;
    private $testRunnerSettings;

    public function __construct(RDataCacheService $cacheService, KernelInterface $kernel, $testRunnerSettings)
    {
        $this->cacheService = $cacheService;
        $this->kernel = $kernel;
        $this->testRunnerSettings = $testRunnerSettings;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("concerto:r:cache")->setDescription("Caches R functions documentation.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        gc_enable();
        $output->writeln("Generating R documentation cache. This might take several minutes...");
        $lastLibrary = null;

        $script_path = $this->kernel->getRootDir() . "/../src/Concerto/PanelBundle/Resources/R/function_documentation.R";
        $p = "\"" . $this->testRunnerSettings['rscript_exec'] . "\" --no-save --no-restore --quiet \"" . $script_path . "\"";
        $process = new Process($p);

        $r_environ_path = $this->testRunnerSettings['r_environ_path'];
        if ($r_environ_path != null) {
            $env = array();
            $env["R_ENVIRON"] = $r_environ_path;
            $process->setEnv($env);
        }

        $process->setTimeout(null);
        $process->run();
        $out = explode("\n", $process->getOutput());
        gc_collect_cycles();
        $this->cacheService->createNewFunctionCacheSet();

        $successful = 0;
        $failed = 0;
        foreach ($out as $line) {
            if (!$line)
                continue;
            $buffer = trim($line);
            unset($line);
            $json = substr($buffer, strpos($buffer, "{"));
            $json = substr($json, 0, strrpos($json, "}") + 1);
            $obj = json_decode(stripslashes($json));
            unset($buffer);
            if (!is_object($obj)) {
                $output->writeln($json);
                $output->writeln(json_last_error_msg());
                $output->writeln("SKIPPED!!!");
                $failed++;
                break;
            } else {
                $names = $obj->fun;
                $lib = $obj->lib;
                $doc = $obj->doc;

                if (!is_array($names)) {
                    $names = array($names);
                }
                if (is_array($lib)) {
                    $lib = $lib[0];
                }
                if (is_array($doc)) {
                    $doc = $doc[0];
                }
                foreach ($names as $name) {
                    if ($this->isDocumentationValid($name, $doc)) {
                        $successful++;
                        $this->cacheService->addRFunction($lib, $name, $doc, $obj->args, $obj->defs);
                        $output->writeln($lib . "::" . $name);
                    }
                }
            }
            unset($json);
            unset($obj);
            gc_collect_cycles();
        }
        $this->cacheService->saveCache();

        $total = $failed + $successful;
        if ($total > 0) {
            $output->writeln("Saved: $successful/" . ($failed + $successful) . " | " . round(100 * $successful / $total) . "%");
        } else {
            $output->writeln("Saved: $successful/" . ($failed + $successful) . " | 0%");
        }
        $output->writeln("R documentation cache generated.");
    }

    private function isDocumentationValid($name, $doc)
    {
        $crawler = new Crawler($doc);
        $crawler = $crawler->filter("h3:contains('Usage')");
        if ($crawler->count() == 0) {
            return false;
        }
        if (strpos($crawler->nextAll()->first()->text(), $name . "(") !== false) {
            return true;
        } else {
            return false;
        }
    }

}
