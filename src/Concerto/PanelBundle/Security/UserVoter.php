<?php

namespace Concerto\PanelBundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\AbstractVoter;
use Concerto\PanelBundle\Entity\User;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class UserVoter extends Voter
{

    const ATTR_ACCESS = 'access';

    protected function supports($attribute, $object)
    {
        return $object instanceof User && in_array($attribute, array(self::ATTR_ACCESS));
    }

    protected function voteOnAttribute($attribute, $object, TokenInterface $token)
    {
        if (!$token->getUser() instanceof UserInterface || !$token->getUser() instanceof User) {
            return false;
        }

        switch ($attribute) {
            case self::ATTR_ACCESS:
                if ($token->getUser()->hasRoleName(User::ROLE_SUPER_ADMIN)) {
                    return true;
                }
                break;
        }
        return false;
    }

}
