<?php

namespace Concerto\TestBundle\Command;

use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OtpInit extends Command
{
    private $googleAuthenticator;

    public function __construct(GoogleAuthenticator $googleAuthenticator)
    {
        parent::__construct();

        $this->googleAuthenticator = $googleAuthenticator;
    }

    protected function configure()
    {
        $this->setName("otp:init")->setDescription("Initializes OTP");
        $this->addArgument("username", InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument("username");
        $secret = $this->googleAuthenticator->generateSecret();
        $user = new OtpUser($username, $secret);
        $qr = $this->googleAuthenticator->getQRContent($user);

        $response = [
            "secret" => $secret,
            "qr" => $qr
        ];

        $output->write(json_encode($response));
    }
}