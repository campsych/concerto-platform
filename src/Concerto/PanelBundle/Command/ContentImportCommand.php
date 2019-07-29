<?php

namespace Concerto\PanelBundle\Command;

use Concerto\PanelBundle\Service\ImportService;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Concerto\PanelBundle\Service\ASectionService;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;
use Concerto\PanelBundle\Entity\User;

class ContentImportCommand extends Command
{
    private $importService;
    private $doctrine;

    public function __construct(ImportService $importService, ManagerRegistry $doctrine)
    {
        $this->importService = $importService;
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
    }

    protected function importContent(InputInterface $input, OutputInterface $output, User $user)
    {
        $output->writeln("importing content...");

        $convert = $input->getOption("convert");
        $clean = $input->getOption("clean");
        $src = $input->getOption("src");

        $files_dir = $input->getArgument("input");
        $instructionsOverride = json_decode($input->getOption("instructions"), true);

        $finder = new Finder();
        $finder->files()->in($files_dir)->name('*.concerto*');

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
                            "rename"
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
            $success = true;
            foreach ($results as $res) {
                if ($res["errors"]) {
                    $success = false;
                    $output->writeln("importing " . $f->getFileName() . " failed!");
                    $output->writeln("content importing failed!");
                    var_dump($res["errors"]);
                    break;
                }
            }
            if ($success) {
                $output->writeln("imported " . $f->getFileName() . " successfully");
            } else {
                break;
            }
        }

        $output->writeln("content importing finished");
    }

    private function importFiles(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("copying files...");
        $dstDir = realpath(__DIR__ . "/../Resources/public/files") . "/";
        $srcDir = realpath($input->getArgument("input")) . "/files/";
        $filesystem = new Filesystem();
        $filesystem->mirror($srcDir, $dstDir);
        $output->writeln("files copied successfully");
        return true;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ASectionService::$securityOn = false;
        $em = $this->doctrine->getManager();

        $userRepo = $em->getRepository("ConcertoPanelBundle:User");
        $user = $userRepo->findOneBy(array());

        if ($input->getOption("files") && !$this->importFiles($input, $output)) {
            return 2;
        }

        $this->importContent($input, $output, $user);
    }

}
