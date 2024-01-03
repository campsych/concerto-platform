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
    private $behindProxy;

    public function __construct($settings, $behindProxy, SamlTokenRepository $samlTokenRepository)
    {
        $this->settings = $settings;
        $this->samlTokenRepository = $samlTokenRepository;
        $this->behindProxy = $behindProxy;

        Utils::setProxyVars($this->behindProxy);
    }

    public function login($redirectTo = null)
    {
        $auth = new Auth($this->settings);
        $auth->login($redirectTo);
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
        $sessionExpiration = $auth->getSessionExpiration();
        if ($sessionExpiration === null) {
            $sessionExpiration = time() + 60 * 60 * 24;
        }
        $token->setExpiresAt($sessionExpiration);
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
        if (!empty($errors)) return false;

        return $metadata;
    }

    public function logout($redirectTo = null, $tokenHash = null)
    {
        $auth = new Auth($this->settings);

        $nameId = null;
        $token = $this->samlTokenRepository->findLatestValid($tokenHash);
        if ($token !== null) {
            $nameId = $token->getNameId();
        }

        $auth->logout($redirectTo, array(), $nameId);
    }

    public function sls($tokenHash, &$stateRelay, &$errors = null)
    {
        $auth = new Auth($this->settings);
        $auth->processSLO(true, null, true);
        $errors = $auth->getErrors();
        if (!empty($errors)) {
            return false;
        }

        $token = $this->samlTokenRepository->findLatestValid($tokenHash);
        if ($token !== null) {
            $token->setRevoked(true);
            $this->samlTokenRepository->save($token);
        }
        if (Utils::getSelfURL() == $stateRelay) {
            $stateRelay = null;
        }
        return true;
    }
}