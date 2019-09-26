<?php

namespace Concerto\APIBundle\Service;

use Concerto\APIBundle\Entity\SamlToken;
use Concerto\APIBundle\Repository\SamlTokenRepository;
use OneLogin\Saml2\Auth;
use OneLogin\Saml2\Utils;

class SamlService
{
    private $settings;
    private $samlTokenRepository;

    public function __construct($settings, SamlTokenRepository $samlTokenRepository)
    {
        $this->settings = $settings;
        $this->samlTokenRepository = $samlTokenRepository;
    }

    public function login()
    {
        $auth = new Auth($this->settings);
        $auth->login();
    }

    public function acs(&$stateRelay = null, &$errors = null)
    {
        $auth = new Auth($this->settings);

        $auth->processResponse();

        $errors = $auth->getErrors();
        if (!empty($errors)) {
            return false;
        }

        if (!$auth->isAuthenticated()) {
            $errors[] = "not authenticated";
            return false;
        }

        $token = new SamlToken();
        $token->setAttributes(json_encode($auth->getAttributes()));
        $token->setExpiresAt($auth->getSessionExpiration());
        $token->setNameId($auth->getNameId());
        $this->samlTokenRepository->save($token);

        if (Utils::getSelfURL() == $stateRelay) {
            $stateRelay = null;
        }
        return $token->getHash();
    }

    public function metadata(&$errors = null)
    {
        $auth = new Auth($this->settings);
        $settings = $auth->getSettings();
        $metadata = $settings->getSPMetadata();
        $errors = $settings->validateMetadata($metadata);
        if(!empty($errors)) return false;

        return $metadata;
    }

    public function logout()
    {
        $auth = new Auth($this->settings);
        $auth->logout();
    }

    public function sls($tokenHash, &$errors = null)
    {
        $auth = new Auth($this->settings);
        $auth->processSLO(true);
        $errors = $auth->getErrors();
        if (!empty($errors)) {
            return false;
        }

        $token = $this->samlTokenRepository->findOneBy(array("hash" => $tokenHash));
        if($token !== null) {
            $token->setRevoked(true);
            $this->samlTokenRepository->save($token);
        }
        return true;
    }
}