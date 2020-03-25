<?php

namespace Concerto\PanelBundle\EventSubscriber;

use Concerto\PanelBundle\Repository\UserRepository;
use Concerto\PanelBundle\Service\AdministrationService;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Concerto\PanelBundle\Entity\User;

class AuthenticationSubscriber implements EventSubscriberInterface
{
    private $userRepository;
    private $adminService;

    public function __construct(UserRepository $userRepository, AdministrationService $adminService)
    {
        $this->userRepository = $userRepository;
        $this->adminService = $adminService;
    }

    public static function getSubscribedEvents()
    {
        return array(
            AuthenticationEvents::AUTHENTICATION_FAILURE => 'onAuthenticationFailure',
            AuthenticationEvents::AUTHENTICATION_SUCCESS => 'onAuthenticationSuccess'
        );
    }

    public function onAuthenticationFailure(AuthenticationFailureEvent $event)
    {
        if ($this->adminService->isFailedAuthLockEnabled()) {
            $username = $event->getAuthenticationToken()->getUsername();
            $maxStreak = (int)$this->adminService->getFailedAuthLockStreak();
            $duration = (int)$this->adminService->getFailedAuthLockTime();

            /** @var User $user */
            $user = $this->userRepository->findOneBy(array("username" => $username));
            if ($user && $user->isAccountNonLocked()) {
                $failedStreak = $user->getFailedAuthenticationStreak() + 1;
                $user->setFailedAuthenticationStreak($failedStreak);
                if ($failedStreak >= $maxStreak) {
                    $until = new \DateTime();
                    $until->setTimestamp(time() + $duration);
                    $user->setLockedUntil($until);
                }
                $this->userRepository->save($user);
            }
        }
    }

    public function onAuthenticationSuccess(AuthenticationEvent $event)
    {
        /** @var User $user */
        $user = $event->getAuthenticationToken()->getUser();
        if (is_a($user, User::class) && $user->getFailedAuthenticationStreak() > 0) {
            $user->setFailedAuthenticationStreak(0);
            $this->userRepository->save($user);
        }
    }

}