<?php

namespace Concerto\PanelBundle\EventSubscriber;

use Concerto\PanelBundle\Entity\AEntity;
use Concerto\PanelBundle\Entity\ATopEntity;
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
                    array_push($entitiesToFlush, $topEntity);
                    $topEntity->updateTopEntity($user);
                }
            }
            $this->topEntitiesToUpdate = [];
            if (!empty($entitiesToFlush)) {
                $entityManager->flush($entitiesToFlush);
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
            $entity instanceof TestWizardParamStep;

        if ($childEntity && !in_array($entity->getTopEntity(), $this->topEntitiesToUpdate)) {
            array_push($this->topEntitiesToUpdate, $entity->getTopEntity());
        }
    }
}