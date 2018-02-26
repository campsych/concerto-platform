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
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class ConcertoSetupCommand extends Command
{
    private $doctrine;
    private $kernel;
    private $encoderFactory;

    public function __construct(ManagerRegistry $doctrine, KernelInterface $kernel, EncoderFactoryInterface $encoderFactory)
    {
        $this->doctrine = $doctrine;
        $this->kernel = $kernel;
        $this->encoderFactory = $encoderFactory;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("concerto:setup")->setDescription("Sets up Concerto.");
        $this->addOption("admin-pass", null, InputOption::VALUE_REQUIRED, "Password for admin user", "admin");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
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

        $em = $this->doctrine->getManager();
        if ($em->getConnection()->getDriver()->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            $trigger_command = $this->getApplication()->get("doctrine:query:sql");
            $sql_file = $this->kernel->locateResource('@ConcertoPanelBundle/Resources/SQL/postgresql_customization.sql');
            $trigger_command->run(new ArrayInput(array(
                'command' => 'doctrine:query:sql',
                'sql' => file_get_contents($sql_file)
            )), $output);
        }

        $output->writeln("checking for user roles...");
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

}
