<?php

namespace Concerto\PanelBundle\Security;

use Symfony\Component\Security\Core\Authorization\Voter\AbstractVoter;
use Concerto\PanelBundle\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;

class UserVoter extends AbstractVoter {

    const ATTR_ACCESS = 'access';

    protected function getSupportedAttributes() {
        return array(self::ATTR_ACCESS);
    }

    protected function getSupportedClasses() {
        return array(
            'Concerto\PanelBundle\Entity\User'
        );
    }

    protected function isGranted($attribute, $obj, $user = null) {
        if (!$user instanceof UserInterface || !$user instanceof User) {
            return false;
        }

        switch ($attribute) {
            case self::ATTR_ACCESS:
                if ($user->hasRoleName(User::ROLE_SUPER_ADMIN)) {
                    return true;
                }
                break;
        }
        return false;
    }

}
