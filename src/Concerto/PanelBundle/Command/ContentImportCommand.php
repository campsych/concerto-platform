<?php

namespace Concerto\PanelBundle\Command;

use Concerto\PanelBundle\Service\AdministrationService;
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

class ContentImportCommand extends Command
{
    private $importService;
    private $adminService;
    private $doctrine;

    public function __construct(ImportService $importService, ManagerRegistry $doctrine, AdministrationService $adminService)
    {
        $this->importService = $importService;
        $this->adminService = $adminService;
        $this->doctrine = $doctrine;

        parent::__construct();
    }

    protected function configure()
    {
        $files_dir = __DIR__ . "/../Resources/starter_content/";

        $this->setName("concerto:content:import")->setDescription("Imports content");
        $this->addArgument("input", InputArgument::OPTIONAL, "Input directory", $files_dir);
        $this->addOption("convert", null, InputOption::VALUE_NONE, "Convert any existing objects to imported version.");
        $this->addOption("clean", null, InputOption::VALUE_NONE, "Remove left-over object?");
        $this->addOption("files", null, InputOption::VALUE_NONE, "Copy files?");
        $this->addOption("src", null, InputOption::VALUE_NONE, "Look for externalized source files?");
        $this->addOption("instructions", "i", InputOption::VALUE_REQUIRED, "Import instructions", "[]");
        $this->addOption("sc", null, InputOption::VALUE_NONE, "Source control ready options set. Combines: --convert --clean --files --src");
    }

    protected function importContent(InputInterface $input, OutputInterface $output, User $user, $sourcePath)
    {
        $output->writeln("importing content...");

        $convert = $input->getOption("convert");
        $clean = $input->getOption("clean");
        $src = $input->getOption("src");
        $instructionsOverride = json_decode($input->getOption("instructions"), true);

        $finder = new Finder();
        $finder->files()->in($sourcePath)->name('*.concerto*');

        foreach ($finder as $f) {
            $this->importService->reset();
            $output->writeln("importing " . $f->getFileName() . "...");

            $instructions = $this->importService->getPreImportStatusFromFile($f->getRealpath())["status"];
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
                            if (array_key_exists($field, $instructionOverrideElem)) {
                                $instructions[$i][$field] = $instructionOverrideElem[$field];
                            }
                        }
                    }
                }
            }

            $results = $this->importService->importFromFile($user, $f->getRealpath(), json_decode(json_encode($instructions), true), false)["import"];
            foreach ($results as $res) {
                if ($res["errors"]) {
                    $success = false;
                    $output->writeln("importing " . $f->getFileName() . " failed!");
                    $output->writeln("content importing failed!");
                    var_dump($res["errors"]);
                    return false;
                }
            }
        }

        $output->writeln("imported " . $f->getFileName() . " successfully");
        return true;
    }

    private function downloadSource(InputInterface $input, OutputInterface $output, $url, &$topTempDir = null)
    {
        $output->writeln("downloading source from $url");
        $fs = new Filesystem();
        $importPath = realpath(__DIR__ . "/../Resources/import");
        $uniquePath = $importPath . "/import_" . uniqid();
        try {
            $fs->mkdir($uniquePath);
        } catch (IOException $ex) {
            $output->writeln($ex->getMessage());
            return false;
        }

        $downloadResult = false;
        try {
            $downloadPath = $uniquePath . "/" . basename(parse_url($url, PHP_URL_PATH));
            $downloadResult = file_put_contents($downloadPath, file_get_contents($url));
        } catch (\Exception $ex) {
        }
        if (!$downloadResult) {
            $output->writeln("couldn't download $url");
            return false;
        }

        $topTempDir = $uniquePath;
        $output->writeln("downloaded source to $downloadPath");
        return $downloadPath;
    }

    private function unzipSource(InputInterface $input, OutputInterface $output, $zipPath, &$topTempDir = null)
    {
        $output->writeln("unzipping content...");
        $extractPath = dirname($zipPath) . "/extract_" . uniqid();
        $fs = new Filesystem();
        try {
            $fs->mkdir($extractPath);
        } catch (IOException $ex) {
            $output->writeln($ex->getMessage());
            return false;
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) === TRUE) {
            $zip->extractTo($extractPath);
            $zip->close();
        } else {
            $output->writeln("couldn't extract $zipPath to $extractPath");
            return false;
        }
        if ($topTempDir === null) $topTempDir = $extractPath;
        $output->writeln("unzipping finished");
        return $extractPath;
    }

    private function cleanTempDir(InputInterface $input, OutputInterface $output, $dirPath)
    {
        $output->writeln("cleaning temp dir $dirPath ...");
        $fs = new Filesystem();
        $fs->remove($dirPath);
        $output->writeln("temp dir cleaned");
    }

    private function importFiles(InputInterface $input, OutputInterface $output, $sourcePath)
    {
        $output->writeln("copying files...");
        $dstDir = realpath(__DIR__ . "/../Resources/public/files") . "/";
        $srcDir = $sourcePath . "/files/";

        if (file_exists($srcDir)) {
            $filesystem = new Filesystem();
            $filesystem->mirror($srcDir, $dstDir);
            $output->writeln("files copied successfully");
        }
        return true;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption("sc")) {
            $input->setOption("convert", true);
            $input->setOption("clean", true);
            $input->setOption("files", true);
            $input->setOption("src", true);
        }
        chdir(realpath(__DIR__ . "/../Resources/starter_content"));

        ASectionService::$securityOn = false;
        $em = $this->doctrine->getManager();

        $userRepo = $em->getRepository("ConcertoPanelBundle:User");
        $user = $userRepo->findOneBy(array());

        $topTempDir = null;
        $sourcePath = $input->getArgument("input");

        //url
        if (stripos($sourcePath, "http://") !== false || stripos($sourcePath, "https://") !== false) {
            $sourcePath = $this->downloadSource($input, $output, $sourcePath, $topTempDir);
            if ($sourcePath === false) {
                return 1;
            }
        }

        //zip
        if (is_file($sourcePath)) {
            $extension = strtolower(pathinfo($sourcePath)["extension"]);
            if ($extension === "zip") {
                $sourcePath = $this->unzipSource($input, $output, $sourcePath);
                if ($sourcePath === false) {
                    return 1;
                }
            }
        }

        if ($input->getOption("files") && !$this->importFiles($input, $output, $sourcePath)) {
            return 1;
        }

        if (!$this->importContent($input, $output, $user, $sourcePath)) {
            return 1;
        }

        if ($topTempDir) {
            $this->cleanTempDir($input, $output, $topTempDir);
        }
        $this->adminService->updateLastImportTime();
    }

}
