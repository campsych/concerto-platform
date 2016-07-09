<?php

namespace Concerto\PanelBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Finder\Finder;
use Concerto\PanelBundle\Entity\User;

class ContentImportCommand extends ContainerAwareCommand {

    protected function configure() {
        $this->setName("concerto:content:import")->setDescription("Imports starter content");
        $this->addArgument("name", InputArgument::OPTIONAL, "Naming rules for imported content", "");
    }

    protected function importStarterContent(InputInterface $input, OutputInterface $output, User $user) {
        $output->writeln("importing starter content...");

        $files_dir = __DIR__ . DIRECTORY_SEPARATOR .
                ".." . DIRECTORY_SEPARATOR .
                "Resources" . DIRECTORY_SEPARATOR .
                "starter_content" . DIRECTORY_SEPARATOR;
        $importService = $this->getContainer()->get('concerto_panel.import_service');

        $finder = new Finder();
        $finder->files()->in($files_dir)->name('*.concerto.json');

        foreach ($finder as $f) {
            $importService->reset();
            if (!$this->preImport($input, $output, $f)) {
                $output->writeln("skipping objects in " . $f->getFileName());
                continue;
            }
            $output->writeln("importing " . $f->getFileName() . "...");
            $results = $importService->importFromFile($user, $f->getRealpath(), $input->getArgument("name"), false);
            $success = true;
            foreach ($results as $res) {
                if ($res["errors"]) {
                    $success = false;
                    $output->writeln("importing " . $f->getFileName() . " failed!");
                    var_dump($res["errors"]);
                    $output->writeln("starter content importing failed!");
                    break;
                }
            }
            if ($success) {
                $output->writeln("imported " . $f->getFileName() . " successfully");
            } else {
                break;
            }
        }

        $output->writeln("starter content importing finished");
    }

    protected function preImport(InputInterface $input, OutputInterface $output, $file) {
        $content = $file->getContents();
        $array = json_decode($content, true);
        if (count($array) == 0) {
            return false;
        }
        $em = $this->getContainer()->get("doctrine")->getManager();
        foreach ($array as $obj) {
            $repo = $em->getRepository("ConcertoPanelBundle:" . $obj["class_name"]);
            if ($repo->findOneBy(array("name" => $obj["name"]))) {
                return false;
            }
        }
        return true;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $em = $this->getContainer()->get("doctrine")->getManager();

        $userRepo = $em->getRepository("ConcertoPanelBundle:User");
        $users = $userRepo->findAll();
        $user = null;
        if (count($users) > 0) {
            $user = $users[0];
        }

        $this->importStarterContent($input, $output, $user);
    }

}
