<?php

namespace Concerto\PanelBundle\EventSubscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;

class EntitySubscriber implements EventSubscriber {

    public function __construct() {
    }
    
    public function postPersist(LifecycleEventArgs $args)
    {
    }

    public function getSubscribedEvents() {
        return array(
        );
    }

}

?>
