<?php

namespace Concerto\PanelBundle\EventSubscriber;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class ResponseSubscriber
{
    const CACHEABLE_ACTIONS = [
        "Concerto\PanelBundle\Controller\ViewTemplateController::contentAction",
        "Concerto\PanelBundle\Controller\ViewTemplateController::htmlAction",
        "Concerto\PanelBundle\Controller\ViewTemplateController::cssAction",
        "Concerto\PanelBundle\Controller\ViewTemplateController::jsAction"
    ];
    const CACHE_MAX_AGE = 60 * 60 * 24;

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $response = $event->getResponse();
        $controller = $event->getRequest()->attributes->get('_controller');
        if (in_array($controller, self::CACHEABLE_ACTIONS)) {
            $response->headers->addCacheControlDirective('max-age', self::CACHE_MAX_AGE);
        }

        $event->setResponse($response);
    }

}