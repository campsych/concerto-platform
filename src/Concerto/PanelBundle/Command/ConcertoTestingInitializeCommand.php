<?php

namespace Concerto\PanelBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Concerto\PanelBundle\Entity\User;
use Concerto\PanelBundle\Entity\Role;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Bundle\FrameworkBundle\Console\Application;

class ConcertoTestingInitializeCommand extends ContainerAwareCommand {

    protected function configure() {
        $this->setName("concerto:testing:initialize")->setDescription("Initializes Protractor test db");
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $env = $input->getOption("env");
        if ($env != "test") {
            $output->writeln("Can only be run in test env!");
            return;
        }

        $em = $this->getContainer()->get("doctrine")->getManager();
        $this->resetDatabase($output);
        $this->createUser($em);
    }

    private function resetDatabase(OutputInterface $output) {
        $app = new Application($this->getContainer()->get("kernel"));
        $app->setAutoExit(false);
        $i = new ArrayInput(array(
            'command' => 'doctrine:schema:drop',
            '--env' => 'test',
            '--force' => null
        ));
        $app->run($i, $output);

        $app = new Application($this->getContainer()->get("kernel"));
        $app->setAutoExit(false);
        $i = new ArrayInput(array(
            'command' => 'doctrine:schema:create',
            '--env' => 'test'
        ));
        $app->run($i, $output);
    }

    private function createUser($em) {
        $encoderFactory = $this->getContainer()->get("security.encoder_factory");

        $role = null;
        $roles = array(
            User::ROLE_FILE,
            User::ROLE_TABLE,
            User::ROLE_TEMPLATE,
            User::ROLE_TEST,
            User::ROLE_WIZARD,
            User::ROLE_SUPER_ADMIN
        );
        foreach ($roles as $r) {
            $role = new Role();
            $role->setName($r);
            $role->setRole($r);
            $em->persist($role);
            $em->flush();
        }

        $user = new User();
        $user->setEmail("username@domain.com");
        $user->setUsername("admin");
        $user->addRole($role);
        $encoder = $encoderFactory->getEncoder($user);
        $password = $encoder->encodePassword("admin", $user->getSalt());
        $passwordConfirmation = $encoder->encodePassword("admin", $user->getSalt());
        $user->setPassword($password);
        $user->setPasswordConfirmation($passwordConfirmation);
        $em->persist($user);
        $em->flush();
    }

}
