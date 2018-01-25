<?php

namespace Concerto\APIBundle\Command;

use FOS\OAuthServerBundle\Model\ClientManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateClientCommand extends Command
{

    private $clientManager;

    public function __construct(ClientManagerInterface $clientManager)
    {
        $this->clientManager = $clientManager;
        
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('oauth-server:client:create')
            ->setDescription('Creates a new client')
            ->addOption(
                'redirect-uri', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Sets redirect uri for client. Use this option multiple times to set multiple redirect URIs.', null
            )
            ->addOption(
                'grant-type', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Sets allowed grant type for client. Use this option multiple times to set multiple grant types..', null
            )
            ->setHelp(
                <<<EOT
                    The <info>%command.name%</info>command creates a new client.

<info>php %command.full_name% [--redirect-uri=...] [--grant-type=...] name</info>

EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $client = $this->clientManager->createClient();
        $client->setRedirectUris($input->getOption('redirect-uri'));
        $client->setAllowedGrantTypes($input->getOption('grant-type'));
        $this->clientManager->updateClient($client);
        $output->writeln(
            sprintf(
                'Added a new client with public id <info>%s</info>, secret <info>%s</info>', $client->getPublicId(), $client->getSecret()
            )
        );
    }

}
