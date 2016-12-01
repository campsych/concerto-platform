<?php

namespace Concerto\PanelBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Concerto\PanelBundle\Service\ASectionService;
use Symfony\Component\Finder\Finder;
use Concerto\PanelBundle\Entity\User;
use Concerto\PanelBundle\Security\ObjectVoter;
use Concerto\PanelBundle\Security\UserVoter;

class ContentImportCommand extends ContainerAwareCommand {

    protected function configure() {
        $files_dir = __DIR__ . DIRECTORY_SEPARATOR .
                ".." . DIRECTORY_SEPARATOR .
                "Resources" . DIRECTORY_SEPARATOR .
                "starter_content" . DIRECTORY_SEPARATOR;

        $this->setName("concerto:content:import")->setDescription("Imports content");
        $this->addArgument("input", InputArgument::OPTIONAL, "Input directory", $files_dir);
        $this->addOption("convert", null, InputOption::VALUE_NONE, "Convert any existing objects to imported version.");
    }

    protected function importStarterContent(InputInterface $input, OutputInterface $output, User $user) {
        $output->writeln("importing content...");

        $convert = $input->getOption("convert");

        $files_dir = $input->getArgument("input");
        $importService = $this->getContainer()->get('concerto_panel.import_service');

        $finder = new Finder();
        $finder->files()->in($files_dir)->name('*.concerto.json');

        foreach ($finder as $f) {
            $importService->reset();
            $output->writeln("importing " . $f->getFileName() . "...");

            $instructions = $importService->getPreImportStatusFromFile($f->getRealpath())["status"];
            for ($i = 0; $i < count($instructions); $i++) {
                if ($convert)
                    $instructions[$i]["action"] = "1";
            }

            $results = $importService->importFromFile($user, $f->getRealpath(), json_decode(json_encode($instructions), true), false)["import"];
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

    protected function execute(InputInterface $input, OutputInterface $output) {
        ASectionService::$securityOn = false;
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
