<?php

namespace Concerto\PanelBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Concerto\PanelBundle\Entity\User;
use Concerto\PanelBundle\Entity\Role;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class ConcertoSetupCommand extends Command
{
    private $doctrine;
    private $kernel;
    private $encoderFactory;
    private $projectDir;
    private $keyPass;

    public function __construct(ManagerRegistry $doctrine, KernelInterface $kernel, EncoderFactoryInterface $encoderFactory, $projectDir, $keyPass)
    {
        $this->doctrine = $doctrine;
        $this->kernel = $kernel;
        $this->encoderFactory = $encoderFactory;
        $this->projectDir = $projectDir;
        $this->keyPass = $keyPass;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("concerto:setup")->setDescription("Sets up Concerto.");
        $this->addOption("admin-pass", null, InputOption::VALUE_REQUIRED, "Password for admin user", "admin");
    }

    private function updateSchema(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("concerto setup (" . $input->getOption("env") . ")");

        $output->writeln("updating database...");
        $command = $this->getApplication()->get("doctrine:schema:update");
        $updateInput = new ArrayInput(array(
            'command' => "doctrine:schema:update",
            '--force' => true
        ));
        $command->run($updateInput, $output);
        $output->writeln("database up to date");
    }

    private function initializeUsers(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("checking for user roles...");
        $em = $this->doctrine->getManager();
        $roleRepo = $em->getRepository("ConcertoPanelBundle:Role");

        $role_names = array(User::ROLE_TEST, User::ROLE_TABLE, User::ROLE_TEMPLATE, User::ROLE_WIZARD, User::ROLE_FILE, User::ROLE_SUPER_ADMIN);
        foreach ($role_names as $role_name) {
            $roles = $roleRepo->findBy(array("name" => $role_name, "role" => $role_name));
            if (count($roles) === 0) {
                $role = new Role();
                $role->setName($role_name);
                $role->setRole($role_name);
                $em->persist($role);
                $em->flush();
                $output->writeln("$role_name created");
            } else {
                $role = $roles[0];
                $output->writeln("$role_name found");
            }
        }

        $output->writeln("checking for default user...");
        $userRepo = $em->getRepository("ConcertoPanelBundle:User");
        $users = $userRepo->findBy(array("username" => "admin"));
        $user = null;
        if (count($users) === 0) {

            $user = new User();
            $encoder = $this->encoderFactory->getEncoder($user);
            $user->setSalt(md5(time()));
            $pass = $encoder->encodePassword($input->getOption("admin-pass"), $user->getSalt());
            $user->addRole($role);
            $user->setUsername("admin");
            $user->setPassword($pass);
            $user->setEmail("admin@mydomain.com");
            $em->persist($user);
            $em->flush();
            $output->writeln("default user created");
        } else {
            $user = $users[0];
            $output->writeln("default user found");
        }
    }

    private function hasJwtKeys()
    {
        $jwtPath = "{$this->projectDir}/app/config/jwt";
        return file_exists("$jwtPath/private.pem") && file_exists("$jwtPath/public.pem");
    }

    private function generateJwtKeys(OutputInterface $output)
    {
        $output->writeln("generating keys...");
        $jwtPath = "{$this->projectDir}/app/config/jwt";

        $cmd = "openssl genpkey -out $jwtPath/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096 -pass pass:{$this->keyPass}";
        $proc = new Process($cmd);
        if($proc->run() === 0) {
            $output->writeln($proc->getOutput());
        } else {
            $output->writeln($proc->getErrorOutput());
            $output->writeln("private key generation failed");
            return false;
        }

        $cmd = "openssl pkey -in $jwtPath/private.pem -out $jwtPath/public.pem -pubout -passin pass:{$this->keyPass}";
        $proc = new Process($cmd);
        if($proc->run() === 0) {
            $output->writeln($proc->getOutput());
        } else {
            $output->writeln($proc->getErrorOutput());
            $output->writeln("public key generation failed");
            return false;
        }
        $output->writeln("key generation successful");
        return true;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->updateSchema($input, $output);
        $this->initializeUsers($input, $output);

        if (!$this->hasJwtKeys()) {
            $output->writeln("no keys found...");
            $this->generateJwtKeys($output);
        } else {
            $output->writeln("keys found");
        }
    }

}
