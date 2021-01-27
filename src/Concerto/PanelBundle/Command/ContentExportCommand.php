<?php

namespace Concerto\PanelBundle\Command;

use Concerto\PanelBundle\Service\AdministrationService;
use Concerto\PanelBundle\Service\ExportService;
use Concerto\PanelBundle\Service\ImportService;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class ContentExportCommand extends Command
{
    private $doctrine;
    private $importService;
    private $exportService;
    private $version;
    private $input;
    private $output;
    private $adminService;
    private $projectDir;

    public function __construct(ManagerRegistry $doctrine, ImportService $importService, ExportService $exportService, $version, AdministrationService $adminService, $projectDir)
    {
        $this->doctrine = $doctrine;
        $this->importService = $importService;
        $this->exportService = $exportService;
        $this->version = $version;
        $this->adminService = $adminService;
        $this->projectDir = $projectDir;

        parent::__construct();
    }

    protected function configure()
    {
        $files_dir = realpath(__DIR__ . "/../Resources/export") . "/";

        $this->setName("concerto:content:export")->setDescription("Exports content");
        $this->addArgument("output", InputArgument::OPTIONAL, "Output directory", $files_dir);
        $this->addOption("single", null, InputOption::VALUE_NONE, "Contain export in a single file?");
        $this->addOption("no-hash", null, InputOption::VALUE_NONE, "Do not include hash?");
        $this->addOption("norm-ids", null, InputOption::VALUE_NONE, "Normalize ids?");
        $this->addOption("files", null, InputOption::VALUE_NONE, "Include files in archive?");
        $this->addOption("yes", "y", InputOption::VALUE_NONE, "Confirm all prompts");
        $this->addOption("src", null, InputOption::VALUE_NONE, "External source files");
        $this->addOption("instructions", "i", InputOption::VALUE_REQUIRED, "Export instructions", null);
        $this->addOption("zip", "z", InputOption::VALUE_REQUIRED, "Zip archive name");
        $this->addOption("sc", null, InputOption::VALUE_NONE, "Source control ready export. Combines --single --no-hash --norm-ids --files --src");
    }

    private function clearExportDirectory()
    {
        $confirmed = $this->input->getOption("yes");
        $path = $this->input->getArgument("output");

        if (!$confirmed) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion("This will clear ALL contents of $path directory. Are you sure you want to continue? [y/n]", false);
            if (!$helper->ask($this->input, $this->output, $question)) {
                return;
            }
        }

        $this->output->writeln("clearing contents of $path ...");

        $fs = new Filesystem();
        $rdi = new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS);
        $rii = new \RecursiveIteratorIterator(new \RecursiveCallbackFilterIterator($rdi, function ($file, $key, $iterator) {
            return strpos($file->getFilename(), ".git") !== 0;
        }));
        $fs->remove($rii);
        $this->output->writeln("contents of $path cleared successfully");
        return true;
    }

    private function exportFiles()
    {
        $this->output->writeln("copying files...");
        $srcDir = "{$this->projectDir}/src/Concerto/PanelBundle/Resources/public/files/";
        $dstDir = realpath($this->input->getArgument("output")) . "/files/";
        $filesystem = new Filesystem();
        $filesystem->mirror($srcDir, $dstDir);
        $this->output->writeln("files copied successfully");
        return true;
    }

    protected function exportContent($instructions = "[]")
    {
        $this->output->writeln("instructions used:");
        $this->output->writeln($instructions);

        $classes = array(
            "DataTable",
            "ViewTemplate",
            "Test",
            "TestWizard",
        );
        $single = $this->input->getOption("single");
        $noHash = $this->input->getOption("no-hash");
        $normIds = $this->input->getOption("norm-ids");
        $decodedInstructions = json_decode($instructions, true);

        $this->output->writeln("exporting content started (" . ($single ? "single file" : "multiple files") . ")");

        $em = $this->doctrine->getManager();
        $dependencies = array();
        $normalizedIdsMap = $normIds ? array() : null;

        foreach ($classes as $class_name) {
            $repo = $em->getRepository("ConcertoPanelBundle:" . $class_name);
            $content = $repo->findBy(array(), array("id" => "ASC"));
            $class_service = $this->importService->serviceMap[$class_name];
            foreach ($content as $ent) {
                if (!$single) {
                    $dependencies = array();
                    $normalizedIdsMap = $normIds ? array() : null;
                }
                $this->exportService->addExportDependency($ent->getId(), $class_service, $dependencies, false, $normalizedIdsMap);

                if (!$single) {
                    $collection = array();
                    if (isset($dependencies["collection"])) {
                        $collection = $this->exportService->convertCollectionToExportable($dependencies["collection"], $decodedInstructions, false, !$noHash);
                    }
                    $result = array("version" => $this->version, "collection" => $this->externalizeSource($collection));
                    $json = Yaml::dump($result, 100, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);

                    $this->saveFile($class_name, $ent->getName(), $json, $this->input->getArgument("output"));
                    $this->output->writeln("ConcertoPanelBundle:" . $class_name . ":" . $ent->getId() . ":" . $ent->getName() . " exported");
                }
            }
        }
        if ($single) {
            $collection = array();
            if (isset($dependencies["collection"])) {
                $collection = $this->exportService->convertCollectionToExportable($dependencies["collection"], $decodedInstructions, false, !$noHash);
            }
            $result = array("version" => $this->version, "collection" => $this->externalizeSource($collection));
            $json = Yaml::dump($result, 100, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);

            $this->saveFile(null, "export", $json, $this->input->getArgument("output"));
            $this->output->writeln("content exported");
        }
        $this->output->writeln("exporting content finished");
    }

    private function saveFile($class_name, $name, $content, $path)
    {
        $fs = new Filesystem();
        if (strripos($path, DIRECTORY_SEPARATOR) !== strlen($path) - 1) {
            $path .= DIRECTORY_SEPARATOR;
        }
        $file_path = $path . ($class_name ? ($class_name . "_") : "") . $name . ".concerto.yml";
        $fs->dumpFile($file_path, $content);
    }

    private function externalizeSource($collection)
    {
        $src = $this->input->getOption("src");
        if (!$src) return $collection;

        $filesystem = new Filesystem();
        $srcDir = realpath($this->input->getArgument("output")) . "/src";
        foreach ($collection as &$obj) {
            switch ($obj["class_name"]) {
                case "Test":
                {
                    //code based
                    if ($obj["type"] == 0 && trim($obj["code"]) !== "") {
                        $path = $srcDir . "/" . ExportService::getTestCodeFilename($obj);
                        $filesystem->dumpFile($path, $obj["code"]);
                        $obj["code"] = null;
                        $this->output->writeln($path . " externalized");
                    }

                    //ports
                    foreach ($obj["nodes"] as &$node) {
                        foreach ($node["ports"] as &$port) {
                            //only input ports
                            if ($port["type"] != 0) continue;
                            $value = trim($port["value"]);
                            $linesNum = substr_count($value, "\n") + 1;
                            if ($linesNum > 2) {
                                $path = $srcDir . "/" . ExportService::getPortValueFilename($obj, $node, $port);
                                $filesystem->dumpFile($path, $port["value"]);
                                $port["value"] = null;
                                $this->output->writeln($path . " externalized");
                            }
                        }
                    }
                    break;
                }
                case "ViewTemplate":
                {
                    //css
                    if (trim($obj["css"]) !== "") {
                        $pathCss = $srcDir . "/" . ExportService::getTemplateCssFilename($obj);
                        $filesystem->dumpFile($pathCss, $obj["css"]);
                        $obj["css"] = null;
                        $this->output->writeln($pathCss . " externalized");
                    }
                    //js
                    if (trim($obj["js"]) !== "") {
                        $pathJs = $srcDir . "/" . ExportService::getTemplateJsFilename($obj);
                        $filesystem->dumpFile($pathJs, $obj["js"]);
                        $obj["js"] = null;
                        $this->output->writeln($pathJs . " externalized");
                    }
                    //html
                    if (trim($obj["html"]) !== "") {
                        $pathHtml = $srcDir . "/" . ExportService::getTemplateHtmlFilename($obj);
                        $filesystem->dumpFile($pathHtml, $obj["html"]);
                        $obj["html"] = null;
                        $this->output->writeln($pathHtml . " externalized");
                    }
                    break;
                }
                case "DataTable":
                {
                    //data
                    if (isset($obj["data"]) && count($obj["data"]) > 0) {
                        $path = $srcDir . "/" . ExportService::getTableDataFilename($obj);
                        $data = Yaml::dump($obj["data"], 100, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
                        $filesystem->dumpFile($path, $data);
                        $obj["data"] = null;
                        $this->output->writeln($path . " externalized");
                    }
                    break;
                }
            }
        }
        return $collection;
    }

    private function zipExport()
    {
        $zipPath = $this->input->getOption("zip");
        if (!$zipPath) return true;
        $dirPath = realpath($this->input->getArgument("output")) . "/";

        $this->output->writeln("zipping $dirPath to $zipPath ...");

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE) === TRUE) {
            $rdi = new \RecursiveDirectoryIterator($dirPath, \FilesystemIterator::SKIP_DOTS);
            $rii = new \RecursiveIteratorIterator($rdi, \RecursiveIteratorIterator::SELF_FIRST);
            foreach ($rii as $item) {
                $path = realpath($item);
                $zipRelativePath = str_replace($dirPath, '', $path);
                if (is_dir($path)) {
                    $zip->addEmptyDir($zipRelativePath);
                } else if (is_file($path)) {
                    $zip->addFile($path, $zipRelativePath);
                }
            }
            $zip->close();
        } else {
            $this->output->writeln("couldn't zip $dirPath to $zipPath");
            return false;
        }
        $this->output->writeln("zipping finished");
        return true;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $instructions = $input->getOption("instructions");
        if ($instructions === null) {
            $instructions = $this->adminService->getContentTransferOptions();
        }

        if ($input->getOption("sc")) {
            $input->setOption("single", true);
            $input->setOption("no-hash", true);
            $input->setOption("norm-ids", true);
            $input->setOption("files", true);
            $input->setOption("src", true);
        }

        if (!$this->clearExportDirectory()) {
            return 1;
        }
        if ($input->getOption("files") && !$this->exportFiles()) {
            return 1;
        }

        $this->exportContent($instructions);

        if ($input->getOption("zip") && !$this->zipExport()) {
            return 1;
        }
        return 0;
    }
}
