<?php

namespace Concerto\PanelBundle\Command;

use Concerto\PanelBundle\Service\AdministrationService;
use Concerto\PanelBundle\Service\GitService;
use Concerto\PanelBundle\Service\ImportService;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Concerto\PanelBundle\Service\ASectionService;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;
use Concerto\PanelBundle\Entity\User;
use Symfony\Component\Translation\TranslatorInterface;

class ContentImportCommand extends Command
{
    private $importService;
    private $adminService;
    private $doctrine;
    private $translator;
    private $gitService;
    private $input;
    private $output;
    private $projectDir;
    private $webUser;

    public function __construct(ImportService $importService, ManagerRegistry $doctrine, AdministrationService $adminService, TranslatorInterface $translator, GitService $gitService, $projectDir, $webUser)
    {
        $this->importService = $importService;
        $this->adminService = $adminService;
        $this->doctrine = $doctrine;
        $this->translator = $translator;
        $this->gitService = $gitService;
        $this->projectDir = $projectDir;
        $this->webUser = $webUser;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("concerto:content:import")->setDescription("Imports content");
        $this->addArgument("input", InputArgument::OPTIONAL, "Input directory", null);
        $this->addOption("convert", null, InputOption::VALUE_NONE, "Convert any existing objects to imported version.");
        $this->addOption("clean", null, InputOption::VALUE_NONE, "Remove left-over object?");
        $this->addOption("files", null, InputOption::VALUE_NONE, "Copy files?");
        $this->addOption("src", null, InputOption::VALUE_NONE, "Look for externalized source files?");
        $this->addOption("instructions", "i", InputOption::VALUE_REQUIRED, "Import instructions", null);
        $this->addOption("sc", null, InputOption::VALUE_NONE, "Source control ready options set. Combines: --convert --clean --files --src");
        $this->addOption("git", null, InputOption::VALUE_NONE, "Ignores input directory, clones/pulls Git repository if needed and imports content from it");
    }

    protected function importContent($sourcePath, $instructionsOverride = "[]")
    {
        $this->output->writeln("importing content...");
        $this->output->writeln("source path used:");
        $this->output->writeln($sourcePath);

        $convert = $this->input->getOption("convert");
        $clean = $this->input->getOption("clean");
        $src = $this->input->getOption("src");
        $this->output->writeln("instructions used:");
        $this->output->writeln($instructionsOverride);
        $instructionsOverride = json_decode($instructionsOverride, true);

        if (is_file($sourcePath)) {
            $pattern = basename($sourcePath);
            $dir = dirname($sourcePath);
        } else {
            $pattern = '*.concerto*';
            $dir = $sourcePath;
        }

        $finder = new Finder();
        $finder->files()->in($dir)->name($pattern);

        foreach ($finder as $f) {
            $this->output->writeln("importing " . $f->getFileName() . "...");

            $instructions = $this->importService->getPreImportStatusFromFile($f->getRealpath(), $errorMessages);
            if ($instructions === false) {
                foreach ($errorMessages as $errorMessage) {
                    $this->output->writeln($this->translator->trans($errorMessage));
                }
                return false;
            }

            for ($i = 0; $i < count($instructions); $i++) {
                if ($convert)
                    $instructions[$i]["action"] = "1";
                if ($clean)
                    $instructions[$i]["clean"] = "1";
                if ($src)
                    $instructions[$i]["src"] = "1";
            }

            foreach ($instructionsOverride as $instructionOverrideElem) {
                for ($i = 0; $i < count($instructions); $i++) {
                    if ($instructions[$i]['class_name'] == $instructionOverrideElem['class_name'] && $instructions[$i]['name'] == $instructionOverrideElem['name']) {
                        $fields = array(
                            "action",
                            "data",
                            "rename",
                            "clean",
                            "src"
                        );
                        foreach ($fields as $field) {
                            if (isset($instructionOverrideElem[$field])) {
                                $instructions[$i][$field] = $instructionOverrideElem[$field];
                            }
                        }
                    }
                }
            }

            $importedSuccessfully = $this->importService->importFromFile($f->getRealpath(), json_decode(json_encode($instructions), true), false, $errorMessages);
            if (!$importedSuccessfully) {
                $this->output->writeln("importing " . $f->getFileName() . " failed!");
                $this->output->writeln("content importing failed!");
                foreach ($errorMessages as $errorMessage) {
                    $this->output->writeln($errorMessage);
                }
                return false;
            }
            $this->output->writeln("imported " . $f->getFileName() . " successfully");
        }
        return true;
    }

    private function downloadSource($url, &$topTempDir = null)
    {
        $this->output->writeln("downloading source from $url");
        $fs = new Filesystem();
        $importPath = "{$this->projectDir}/src/Concerto/PanelBundle/Resources/import";
        $uniquePath = $importPath . "/import_" . uniqid();
        try {
            $fs->mkdir($uniquePath);
        } catch (IOException $ex) {
            $this->output->writeln($ex->getMessage());
            return false;
        }

        $downloadResult = false;
        try {
            $downloadPath = $uniquePath . "/" . basename(parse_url($url, PHP_URL_PATH));
            $downloadResult = file_put_contents($downloadPath, file_get_contents($url));
        } catch (\Exception $ex) {
        }
        if (!$downloadResult) {
            $this->output->writeln("couldn't download $url");
            return false;
        }

        $topTempDir = $uniquePath;
        $this->output->writeln("downloaded source to $downloadPath");
        return $downloadPath;
    }

    private function unzipSource($zipPath, &$topTempDir = null)
    {
        $this->output->writeln("unzipping content...");
        $extractPath = dirname($zipPath) . "/extract_" . uniqid();
        $fs = new Filesystem();
        try {
            $fs->mkdir($extractPath);
        } catch (IOException $ex) {
            $this->output->writeln($ex->getMessage());
            return false;
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) === TRUE) {
            $zip->extractTo($extractPath);
            $zip->close();
        } else {
            $this->output->writeln("couldn't extract $zipPath to $extractPath");
            return false;
        }
        if ($topTempDir === null) $topTempDir = $extractPath;
        $this->output->writeln("unzipping finished");
        return $extractPath;
    }

    private function cleanTempDir($dirPath)
    {
        $this->output->writeln("cleaning temp dir $dirPath ...");
        $fs = new Filesystem();
        $fs->remove($dirPath);
        $this->output->writeln("temp dir cleaned");
    }

    private function importFiles($sourcePath)
    {
        $this->output->writeln("copying files...");
        $dstDir = "{$this->projectDir}/src/Concerto/PanelBundle/Resources/public/files/";
        $srcDir = $sourcePath . "/files/";

        if (file_exists($srcDir)) {
            $filesystem = new Filesystem();
            $filesystem->mirror($srcDir, $dstDir);
            $filesystem->chown($dstDir, $this->webUser, true);
            $this->output->writeln("files copied successfully");
        }
        return true;
    }

    private function prepareGit($instructions)
    {
        $this->output->writeln("preparing Git");

        if (!$this->gitService->scheduleTaskGitEnable(null, null, null, null, $instructions, true, $gitEnableOutput)) {
            $this->output->writeln($gitEnableOutput);
            return false;
        }
        $this->output->write($gitEnableOutput);

        if (!$this->gitService->gitPull($gitPullOutput, $errorMessages)) {
            foreach ($errorMessages as $errorMessage) {
                $this->output->writeln($errorMessage);
            }
            return false;
        }
        $this->output->write($gitPullOutput);

        $this->output->writeln("Git prepared");
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
            $input->setOption("convert", true);
            $input->setOption("clean", true);
            $input->setOption("files", true);
            $input->setOption("src", true);
        }
        chdir("{$this->projectDir}/src/Concerto/PanelBundle/Resources/starter_content");

        ASectionService::$securityOn = false;

        $topTempDir = null;
        $sourcePath = $input->getArgument("input");
        if ($sourcePath === null) $sourcePath = $this->adminService->getContentUrl();

        if ($input->getOption("git")) {
            if ($input->getOption("instructions") === null) {
                $instructions = $this->adminService->getContentTransferOptions();
            }

            if (!$this->prepareGit($instructions)) {
                return 1;
            }
            $sourcePath = $this->gitService->getGitRepoPath();
        }

        //url
        if (stripos($sourcePath, "http://") !== false || stripos($sourcePath, "https://") !== false) {
            $sourcePath = $this->downloadSource($sourcePath, $topTempDir);
            if ($sourcePath === false) {
                return 1;
            }
        }

        //zip
        if (is_file($sourcePath)) {
            $extension = strtolower(pathinfo($sourcePath)["extension"]);
            if ($extension === "zip") {
                $sourcePath = $this->unzipSource($sourcePath);
                if ($sourcePath === false) {
                    return 1;
                }
            }
        }

        if ($input->getOption("files") && !$this->importFiles($sourcePath)) {
            return 1;
        }

        if (!$this->importContent($sourcePath, $instructions)) {
            return 1;
        }

        if ($topTempDir) {
            $this->cleanTempDir($topTempDir);
        }
        $this->adminService->updateLastImportTime();
    }

}
