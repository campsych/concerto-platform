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
        $files_dir = __DIR__ . DIRECTORY_SEPARATOR .
            ".." . DIRECTORY_SEPARATOR .
            "Resources" . DIRECTORY_SEPARATOR .
            "starter_content" . DIRECTORY_SEPARATOR;

        $this->setName("concerto:content:import")->setDescription("Imports content");
        $this->addArgument("input", InputArgument::OPTIONAL, "Input directory", $files_dir);
        $this->addOption("convert", null, InputOption::VALUE_NONE, "Convert any existing objects to imported version.");
    }

    protected function importStarterContent(InputInterface $input, OutputInterface $output, User $user)
    {
        $output->writeln("importing content...");

        $convert = $input->getOption("convert");

        $files_dir = $input->getArgument("input");

        $finder = new Finder();
        $finder->files()->in($files_dir)->name('*.concerto.json');

        foreach ($finder as $f) {
            $this->importService->reset();
            $output->writeln("importing " . $f->getFileName() . "...");

            $instructions = $this->importService->getPreImportStatusFromFile($f->getRealpath())["status"];
            for ($i = 0; $i < count($instructions); $i++) {
                if ($convert)
                    $instructions[$i]["action"] = "1";
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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ASectionService::$securityOn = false;
        $em = $this->doctrine->getManager();

        $userRepo = $em->getRepository("ConcertoPanelBundle:User");
        $users = $userRepo->findAll();
        $user = null;
        if (count($users) > 0) {
            $user = $users[0];
        }

        $this->importStarterContent($input, $output, $user);
    }

}
