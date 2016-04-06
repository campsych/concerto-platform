<?php

namespace Concerto\PanelBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Filesystem\Filesystem;

class ContentExportCommand extends ContainerAwareCommand {

    protected function configure() {
        $files_dir = __DIR__ . DIRECTORY_SEPARATOR .
                ".." . DIRECTORY_SEPARATOR .
                "Resources" . DIRECTORY_SEPARATOR .
                "starter_content" . DIRECTORY_SEPARATOR;

        $this->setName("concerto:content:export")->setDescription("Exports starter content");
        $this->addArgument("output", InputArgument::OPTIONAL, "Output path", $files_dir);
        $this->addOption("set-protected", null, InputOption::VALUE_NONE, "Set all starter content objects as protected (except for data tables)");
    }

    protected function exportStarterContent(InputInterface $input, OutputInterface $output) {
        $output->writeln("exporting starter content started");
        $classes = array(
            "DataTable",
            "Test",
            "ViewTemplate"
        );
        $importService = $this->getContainer()->get('concerto_panel.import_service');
        $em = $this->getContainer()->get("doctrine")->getManager();
        foreach ($classes as $class_name) {
            $service = $importService->serviceMap[$class_name];
            $repo = $em->getRepository("ConcertoPanelBundle:" . $class_name);
            $collection = $repo->findBy(array("starterContent" => 1));
            foreach ($collection as $ent) {
                if (strpos($ent->getName(), "source_") === 0) {
                    continue;
                }
                if ($class_name == "DataTable") {
                    $ent = $service->assignColumnCollection(array($ent))[0];
                }
                $current_checksum = $ent->getChecksum();

                $arr = $service->entityToArray($ent);
                $arr_check = $arr;
                unset($arr_check["checksum"]);
                unset($arr_check["revision"]);

                $json_check = json_encode($arr_check);
                $new_checksum = md5($json_check);

                if ($current_checksum !== $new_checksum) {
                    $ent->incrementRevision();
                    $ent->setChecksum($new_checksum);
                    $output->writeln("ConcertoPanelBundle:" . $class_name . ":" . $ent->getId() . ":" . $ent->getName() . " modification detected");
                    $arr = $service->entityToArray($ent);
                }
                $em->persist($ent);
                $json = json_encode(array($arr), JSON_PRETTY_PRINT);

                $this->saveFile($class_name, $ent->getName(), $json, $input->getArgument("output"));
                $output->writeln("ConcertoPanelBundle:" . $class_name . ":" . $ent->getId() . ":" . $ent->getName() . " exported");
            }
        }
        $em->flush();
        $output->writeln("exporting starter content finished");
    }

    private function saveFile($class_name, $name, $content, $path) {
        $fs = new Filesystem();
        if (strripos($path, DIRECTORY_SEPARATOR) !== strlen($path) - 1) {
            $path.=DIRECTORY_SEPARATOR;
        }
        $file_path = $path . $class_name . "_" . $name . ".concerto.json";
        $fs->dumpFile($file_path, $content);
    }
    
    protected function protectStarterContent(InputInterface $input, OutputInterface $output) {
        $output->writeln("setting starter content as protected");
        $classes = array(
            "Test",
            "TestWizard",
            "ViewTemplate"
        );
        $em = $this->getContainer()->get("doctrine")->getManager();
        foreach ($classes as $class_name) {
            $repo = $em->getRepository("ConcertoPanelBundle:" . $class_name);
            $collection = $repo->findBy(array("starterContent" => 1));
            foreach ($collection as $ent) {
                if (!$ent->isProtected()) {
                    $ent->setProtected(true);
                    $em->persist($ent);
                    $output->writeln("ConcertoPanelBundle:" . $class_name . ":" . $ent->getId() . ":" . $ent->getName() . " set to protected");
                }
            }
        }
        $em->flush();
    }

    protected function setGlobalIds(InputInterface $input, OutputInterface $output) {
        $output->writeln("assigning missing global ids");

        $classes = array(
            "DataTable",
            "Test",
            "TestNode",
            "TestNodeConnection",
            "TestNodePort",
            "TestVariable",
            "TestWizard",
            "TestWizardParam",
            "TestWizardStep",
            "ViewTemplate"
        );

        $em = $this->getContainer()->get("doctrine")->getManager();
        foreach ($classes as $class_name) {
            $repo = $em->getRepository("ConcertoPanelBundle:" . $class_name);
            $collection = $repo->findAll();
            foreach ($collection as $ent) {
                if ($ent->getGlobalId() === null) {
                    $ent->setGlobalId();
                    $em->persist($ent);
                }
            }
        }
        $em->flush();
        $output->writeln("finished assigning global ids");
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        if ($input->getOption("set-protected")) {
            $this->protectStarterContent($input, $output);
        }
        $this->setGlobalIds($input, $output);

        $this->exportStarterContent($input, $output);
    }

}
