<?php

namespace Concerto\PanelBundle\Command;

use Concerto\PanelBundle\Service\ImportService;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Filesystem\Filesystem;

class ContentExportCommand extends Command
{
    private $doctrine;
    private $importService;
    private $version;

    public function __construct(ManagerRegistry $doctrine, ImportService $importService, $version)
    {
        $this->doctrine = $doctrine;
        $this->importService = $importService;
        $this->version = $version;

        parent::__construct();
    }

    protected function configure()
    {
        $files_dir = __DIR__ . DIRECTORY_SEPARATOR .
            ".." . DIRECTORY_SEPARATOR .
            "Resources" . DIRECTORY_SEPARATOR .
            "starter_content" . DIRECTORY_SEPARATOR;

        $this->setName("concerto:content:export")->setDescription("Exports starter content");
        $this->addArgument("output", InputArgument::OPTIONAL, "Output directory", $files_dir);
    }

    protected function exportStarterContent(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("exporting starter content started");
        $classes = array(
            "DataTable",
            "Test",
            "TestWizard",
            "ViewTemplate"
        );
        $em = $this->doctrine->getManager();
        foreach ($classes as $class_name) {
            $repo = $em->getRepository("ConcertoPanelBundle:" . $class_name);
            $starter_content = $repo->findBy(array("starterContent" => true));
            $class_service = $this->importService->serviceMap[$class_name];
            foreach ($starter_content as $ent) {
                $dependencies = array();
                $ent = $class_service->get($ent->getId(), false, false);
                $ent->jsonSerialize($dependencies);

                if (array_key_exists("ids", $dependencies)) {
                    foreach ($dependencies["ids"] as $k => $v) {
                        $ids_service = $this->importService->serviceMap[$k];
                        foreach ($v as $id) {
                            $ids_service->get($id, false, false)->jsonSerialize($dependencies);
                        }
                    }
                }

                $collection = array();
                if (array_key_exists("collection", $dependencies)) {
                    foreach ($dependencies["collection"] as $elem) {
                        $export_elem = $elem;
                        $elem_service = $this->importService->serviceMap[$elem["class_name"]];
                        $elem_class = "\\Concerto\\PanelBundle\\Entity\\" . $elem["class_name"];
                        $export_elem["hash"] = $elem_class::getArrayHash($elem);
                        $export_elem = $elem_service->convertToExportable($export_elem);
                        array_push($collection, $export_elem);
                    }
                }
                $result = array("version" => $this->version, "collection" => $collection);
                $json = json_encode($result, JSON_PRETTY_PRINT);

                $this->saveFile($class_name, $ent->getName(), $json, $input->getArgument("output"));
                $output->writeln("ConcertoPanelBundle:" . $class_name . ":" . $ent->getId() . ":" . $ent->getName() . " exported");
            }
        }
        $em->flush();
        $output->writeln("exporting starter content finished");
    }

    private function saveFile($class_name, $name, $content, $path)
    {
        $fs = new Filesystem();
        if (strripos($path, DIRECTORY_SEPARATOR) !== strlen($path) - 1) {
            $path .= DIRECTORY_SEPARATOR;
        }
        $file_path = $path . $class_name . "_" . $name . ".concerto.json";
        $fs->dumpFile($file_path, $content);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->exportStarterContent($input, $output);
    }

}
