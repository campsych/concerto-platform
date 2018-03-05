<?php

namespace Concerto\PanelBundle\EventSubscriber;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LocaleSubscriber implements EventSubscriberInterface {

    private $defaultLocale;

    public function __construct($defaultLocale = "en_GB") {
        $this->defaultLocale = $defaultLocale;
    }

    public function onKernelRequest(GetResponseEvent $event) {
        $request = $event->getRequest();
        if (!$request->hasPreviousSession()) {
            return;
        }

        if ($locale = $request->attributes->get("_locale")) {
            $request->getSession()->set("_locale", $locale);
        } else {
            $request->setLocale($request->getSession()->get("_locale", $this->defaultLocale));
        }
    }

    public static function getSubscribedEvents() {
        return array(
            KernelEvents::REQUEST => array(array('onKernelRequest', 17))
        );
    }

}
