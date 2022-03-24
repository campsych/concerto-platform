<?php

namespace Concerto\TestBundle\Command;

use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;

class OtpUser implements TwoFactorInterface
{
    private $username;
    private $secret;

    public function __construct($username, $secret)
    {
        $this->username = $username;
        $this->secret = $secret;
    }

    public function isGoogleAuthenticatorEnabled(): bool
    {
        return true;
    }

    public function getGoogleAuthenticatorUsername(): string
    {
        return $this->username;
    }

    public function getGoogleAuthenticatorSecret(): ?string
    {
        return $this->secret;
    }
}