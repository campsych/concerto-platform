<?php

namespace Concerto\PanelBundle\EventSubscriber;

use Concerto\PanelBundle\Entity\AEntity;
use Concerto\PanelBundle\Entity\ATopEntity;
use Concerto\PanelBundle\Entity\TestNode;
use Concerto\PanelBundle\Entity\TestNodeConnection;
use Concerto\PanelBundle\Entity\TestNodePort;
use Concerto\PanelBundle\Entity\TestVariable;
use Concerto\PanelBundle\Entity\TestWizardParam;
use Concerto\PanelBundle\Entity\TestWizardStep;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class EntitySubscriber implements EventSubscriber
{
    private $securityTokenStorage;
    private $topEntitiesToUpdate;

    public function __construct(TokenStorageInterface $securityTokenStorage)
    {
        $this->securityTokenStorage = $securityTokenStorage;
        $this->topEntitiesToUpdate = [];
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
        $this->addTopEntityForUpdate($args->getObject());
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $this->addTopEntityForUpdate($args->getObject());
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $this->addTopEntityForUpdate($args->getObject());
    }

    public function postFlush(PostFlushEventArgs $args)
    {
        if (!empty($this->topEntitiesToUpdate)) {
            $entityManager = $args->getEntityManager();
            $user = null;
            $token = $this->securityTokenStorage->getToken();
            if ($token !== null) $user = $token->getUser();
            $entitiesToFlush = [];
            foreach ($this->topEntitiesToUpdate as $topEntity) {
                if ($entityManager->contains($topEntity)) {
                    $topEntity->updateTopEntity($user);
                    array_push($entitiesToFlush, $topEntity);
                }
            }
            $this->topEntitiesToUpdate = [];
            if (!empty($entitiesToFlush)) {
                $entityManager->flush();
            }
        }
    }

    public function getSubscribedEvents()
    {
        return array(
            Events::prePersist,
            Events::preUpdate,
            Events::preRemove,
            Events::postFlush
        );
    }

    private function addTopEntityForUpdate($entity)
    {
        $childEntity = $entity instanceof TestNode ||
            $entity instanceof TestNodeConnection ||
            $entity instanceof TestNodePort ||
            $entity instanceof TestVariable ||
            $entity instanceof TestWizardParam ||
            $entity instanceof TestWizardStep;

        if ($childEntity && !in_array($entity->getTopEntity(), $this->topEntitiesToUpdate)) {
            array_push($this->topEntitiesToUpdate, $entity->getTopEntity());
        }
    }
}