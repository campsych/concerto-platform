<?php

namespace Concerto\PanelBundle\Service;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Session\Session;

class PanelService {

    public function getLoginErrors($authError, Session $session) {
        $errors = null;
        if ($authError !== null) {
            $errors = $authError;
        } else {
            $errors = $session->get(Security::AUTHENTICATION_ERROR);
            $session->remove(Security::AUTHENTICATION_ERROR);
        }
        return $errors;
    }
}
