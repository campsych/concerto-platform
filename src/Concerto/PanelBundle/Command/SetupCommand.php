<?php

namespace Concerto\PanelBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Concerto\PanelBundle\Entity\User;
use Concerto\PanelBundle\Entity\Role;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Concerto\PanelBundle\Service\SystemCheckService;

class SetupCommand extends ContainerAwareCommand {

    protected function configure() {
        $this->setName("concerto:setup")->setDescription("Sets up Concerto.");
        $this->addOption("check", null, InputOption::VALUE_NONE, "Perform system checks?");
    }

    protected function verifySystemSoftware(InputInterface $input, OutputInterface $output, SystemCheckService $syscheck_service) {
        $executables = $this->getContainer()->getParameter('requirements')['executables'];

        foreach ($executables as $name => $requirements) {
            $output->writeln("   -> checking if " . $requirements['command'] .
                    ( isset($requirements['version']['min']) ? " (version >= " . $requirements['version']['min'] . ")" : "" ) .
                    " is available in this system...");

            $status = $syscheck_service->checkExecutable($name, $requirements);
            if (!$status->isOk())
                throw new InvalidConfigurationException($status->getErrorsString());
            $output->writeln("     -> all ok");
        }
    }

    protected function verifyConcertoDirectories(InputInterface $input, OutputInterface $output, SystemCheckService $syscheck_service) {

        $directories = $this->getContainer()->getParameter('requirements')['paths'];
        foreach ($directories as $name => $requirements) {
            $nicename = SystemCheckService::getDirNicename($name, $requirements);
            $output->writeln("   -> verifying {$nicename}, please wait...");
            $status = $syscheck_service->checkPath($name, $requirements);
            if (!$status->isOk())
                throw new InvalidConfigurationException($status->getErrorsString());
            if ($status->wasFixed())
                $output->writeln("     -> fixed a problem discovered with $nicename");
            $output->writeln("     -> all ok");
        }
    }

    protected function verifySystemConfiguration(InputInterface $input, OutputInterface $output) {

        $syscheck_service = $this->getContainer()->get('concerto_panel.system_check_service');
        $output->writeln("verifying system configuration...");

        $output->writeln(" -> verifying installed software...");
        $this->verifySystemSoftware($input, $output, $syscheck_service);

        $output->writeln(" -> verifying Concerto directories...");
        $this->verifyConcertoDirectories($input, $output, $syscheck_service);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln("concerto setup (" . $input->getOption("env") . ")");

        if ($input->getOption("check")) {
            $this->verifySystemConfiguration($input, $output);
        }

        $output->writeln("updating database...");
        $command = $this->getApplication()->get("doctrine:schema:update");
        $updateInput = new ArrayInput(array(
            'command' => "doctrine:schema:update",
            '--force' => true
        ));
        $command->run($updateInput, $output);
        $output->writeln("database up to date");

        $em = $this->getContainer()->get("doctrine")->getManager();
        if ($em->getConnection()->getDriver()->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            $trigger_command = $this->getApplication()->get("doctrine:query:sql");
            $sql_file = $this->getContainer()->get('kernel')->locateResource('@ConcertoPanelBundle/Resources/SQL/postgresql_customization.sql');
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

            $factory = $this->getContainer()->get('security.encoder_factory');
            $user = new User();
            $encoder = $factory->getEncoder($user);
            $user->setSalt(md5(time()));
            $pass = $encoder->encodePassword("admin", $user->getSalt());
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
