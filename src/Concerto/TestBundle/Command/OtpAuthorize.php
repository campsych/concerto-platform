<?php

namespace Concerto\TestBundle\Command;

use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OtpAuthorize extends Command
{
    private $googleAuthenticator;

    public function __construct(GoogleAuthenticator $googleAuthenticator)
    {
        parent::__construct();

        $this->googleAuthenticator = $googleAuthenticator;
    }

    protected function configure()
    {
        $this->setName("otp:authorize")->setDescription("OTP authorization");
        $this->addArgument("username", InputArgument::REQUIRED);
        $this->addArgument("secret", InputArgument::REQUIRED);
        $this->addArgument("code", InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument("username");
        $secret = $input->getArgument("secret");
        $code = $input->getArgument("code");
        $user = new OtpUser($username, $secret);
        $success = $this->googleAuthenticator->checkCode($user, $code);

        $output->write(json_encode(["success" => $success]));
    }
}