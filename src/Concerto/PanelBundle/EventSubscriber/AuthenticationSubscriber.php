<?php

namespace Concerto\PanelBundle\EventSubscriber;

use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;

class AuthenticationSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents()
    {
        return array(
            AuthenticationEvents::AUTHENTICATION_FAILURE => 'onAuthenticationFailure',
            AuthenticationEvents::AUTHENTICATION_SUCCESS => 'onAuthenticationSuccess'
        );
    }

    public function onAuthenticationFailure(AuthenticationFailureEvent $event)
    {
        sleep(1);
    }

    public function onAuthenticationSuccess(AuthenticationEvent $event)
    {
    }

}