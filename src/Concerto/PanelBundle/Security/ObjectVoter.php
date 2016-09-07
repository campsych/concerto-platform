<?php

namespace Concerto\PanelBundle\Security;

use Symfony\Component\Security\Core\Authorization\Voter\AbstractVoter;
use Concerto\PanelBundle\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Concerto\PanelBundle\Entity\ATopEntity;

class ObjectVoter extends AbstractVoter {

    const ATTR_ACCESS = 'access';

    protected function getSupportedAttributes() {
        return array(self::ATTR_ACCESS);
    }

    protected function getSupportedClasses() {
        return array(
            'Concerto\PanelBundle\Entity\DataTable',
            'Concerto\PanelBundle\Entity\Test',
            'Concerto\PanelBundle\Entity\TestNode',
            'Concerto\PanelBundle\Entity\TestNodeConnection',
            'Concerto\PanelBundle\Entity\TestNodePort',
            'Concerto\PanelBundle\Entity\TestSessionLog',
            'Concerto\PanelBundle\Entity\TestVariable',
            'Concerto\PanelBundle\Entity\TestWizard',
            'Concerto\PanelBundle\Entity\TestWizardParam',
            'Concerto\PanelBundle\Entity\TestWizardStep',
            'Concerto\PanelBundle\Entity\ViewTemplate'
        );
    }

    protected function isGranted($attribute, $obj, $user = null) {
        if (!$user instanceof UserInterface || !$user instanceof User) {
            return false;
        }

        switch ($attribute) {
            case self::ATTR_ACCESS:
                //super admin or 
                if ($user->hasRoleName(User::ROLE_SUPER_ADMIN)) {
                    return true;
                }
                 //public
                if ($obj->getAccessibility() == ATopEntity::ACCESS_PUBLIC) {
                    return true;
                }
                //owner
                if ($obj->getOwner() && $user->getId() == $obj->getOwner()->getId()) {
                    return true;
                }
                //group
                if ($obj->getAccessibility() == ATopEntity::ACCESS_GROUP && $obj->hasAnyFromGroup($user->getGroupsArray())) {
                    return true;
                }
                break;
        }
        return false;
    }

}
