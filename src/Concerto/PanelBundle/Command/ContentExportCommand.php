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
            "TestWizard",
            "ViewTemplate"
        );
        $em = $this->getContainer()->get("doctrine")->getManager();
        foreach ($classes as $class_name) {
            $repo = $em->getRepository("ConcertoPanelBundle:" . $class_name);
            $collection = $repo->findBy(array("starterContent" => 1));
            $importService = $this->getContainer()->get('concerto_panel.import_service');
            $service = $importService->serviceMap[$class_name];
            foreach ($collection as $ent) {
                $ent = $service->get($ent->getId(), false, false);
                $arr = $service->entityToArray($ent);
                $arr["hash"] = $ent->getHash();
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
            $path .= DIRECTORY_SEPARATOR;
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

    protected function execute(InputInterface $input, OutputInterface $output) {
        if ($input->getOption("set-protected")) {
            $this->protectStarterContent($input, $output);
        }

        $this->exportStarterContent($input, $output);
    }

}
