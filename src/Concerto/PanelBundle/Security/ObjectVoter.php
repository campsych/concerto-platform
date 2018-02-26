<?php

namespace Concerto\PanelBundle\Security;

use Concerto\PanelBundle\Entity\DataTable;
use Concerto\PanelBundle\Entity\Test;
use Concerto\PanelBundle\Entity\TestNode;
use Concerto\PanelBundle\Entity\TestNodeConnection;
use Concerto\PanelBundle\Entity\TestNodePort;
use Concerto\PanelBundle\Entity\TestSessionLog;
use Concerto\PanelBundle\Entity\TestVariable;
use Concerto\PanelBundle\Entity\TestWizard;
use Concerto\PanelBundle\Entity\TestWizardParam;
use Concerto\PanelBundle\Entity\TestWizardStep;
use Concerto\PanelBundle\Entity\User;
use Concerto\PanelBundle\Entity\ViewTemplate;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;
use Concerto\PanelBundle\Entity\ATopEntity;

class ObjectVoter extends Voter
{

    const ATTR_ACCESS = 'access';

    protected function supports($attribute, $object)
    {
        return ($object instanceof DataTable ||
                $object instanceof Test ||
                $object instanceof TestNode ||
                $object instanceof TestNodeConnection ||
                $object instanceof TestNodePort ||
                $object instanceof TestSessionLog ||
                $object instanceof TestVariable ||
                $object instanceof TestWizard ||
                $object instanceof TestWizardParam ||
                $object instanceof TestWizardStep ||
                $object instanceof ViewTemplate) && in_array($attribute, array(self::ATTR_ACCESS));
    }

    protected function voteOnAttribute($attribute, $object, TokenInterface $token)
    {
        if (!$token->getUser() instanceof UserInterface || !$token->getUser() instanceof User) {
            return false;
        }

        switch ($attribute) {
            case self::ATTR_ACCESS:
                //super admin or
                if ($token->getUser()->hasRoleName(User::ROLE_SUPER_ADMIN)) {
                    return true;
                }
                //public
                if ($object->getAccessibility() == ATopEntity::ACCESS_PUBLIC) {
                    return true;
                }
                //owner
                if ($object->getOwner() && $token->getUser()->getId() == $object->getOwner()->getId()) {
                    return true;
                }
                //group
                if ($object->getAccessibility() == ATopEntity::ACCESS_GROUP && $object->hasAnyFromGroup($token->getUser()->getGroupsArray())) {
                    return true;
                }
                break;
        }
        return false;
    }
}
