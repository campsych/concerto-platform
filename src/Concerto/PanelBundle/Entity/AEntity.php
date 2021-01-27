<?php

namespace Concerto\PanelBundle\Entity;

use Concerto\PanelBundle\Entity\User;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use DateTime;

abstract class AEntity
{

    /**
     * @var integer
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    public function __construct()
    {
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ATopEntity
     */
    public function getTopEntity()
    {
        return $this;
    }

    public function updateTopEntity(User $user = null)
    {
        $this->getTopEntity()->setUpdated(new DateTime("now"));
        $this->getTopEntity()->setUpdatedBy($user);
    }

    /**
     * @return User|null
     */
    public function getLockBy()
    {
        return null;
    }

    /** @ORM\PreUpdate() */
    public function preUpdate()
    {
        $this->getTopEntity()->setUpdated(new DateTime("now"));
    }

    public static function reserveDependency(&$dependencies, $class, $id)
    {
        if (!isset($dependencies["reservations"]))
            $dependencies["reservations"] = array();
        if (!isset($dependencies["reservations"][$class]))
            $dependencies["reservations"][$class] = array();
        if (!in_array($id, $dependencies["reservations"][$class]))
            array_push($dependencies["reservations"][$class], $id);
    }

    public static function isDependencyReserved($dependencies, $class, $id)
    {
        if (!isset($dependencies["reservations"]))
            return false;
        if (!isset($dependencies["reservations"][$class]))
            return false;
        return in_array($id, $dependencies["reservations"][$class]);
    }

    public static function addDependency(&$dependencies, $serialized)
    {
        if (!isset($dependencies["collection"])) {
            $dependencies["collection"] = array();
        }
        array_push($dependencies["collection"], $serialized);
    }

    public static function jsonSerializeArray($array, &$dependencies = array(), &$normalizedIdsMap = null)
    {
        $result = array();
        foreach ($array as $ent) {
            array_push($result, $ent->jsonSerialize($dependencies, $normalizedIdsMap));
        }
        return $result;
    }

    public static function normalizeId($class, $id, &$normalizedIdsMap = array())
    {
        if ($id === null) return null;
        if (!isset($normalizedIdsMap[$class])) {
            $normalizedIdsMap[$class] = array();
        }
        if (!isset($normalizedIdsMap[$class][$id])) {
            $normalizedIdsMap[$class][$id] = count($normalizedIdsMap[$class]) + 1;
        }
        return $normalizedIdsMap[$class][$id];
    }

    public static function getEntityCollectionHash($collection)
    {
        $result = "";
        foreach ($collection as $entity) {
            $result .= $entity->getEntityHash();
        }
        return $result;
    }

    public abstract function getOwner();

    public abstract function getAccessibility();

    public abstract function hasAnyFromGroup($other_groups);

    public abstract function getEntityHash();
}
