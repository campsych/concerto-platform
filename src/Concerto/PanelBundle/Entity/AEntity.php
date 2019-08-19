<?php

namespace Concerto\PanelBundle\Entity;

use Concerto\PanelBundle\Entity\User;
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

    /**
     *
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    protected $updated;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $updatedBy;

    /**
     *
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    protected $created;

    public function __construct()
    {
        $this->created = new DateTime("now");
        $this->updated = new DateTime("now");
        $this->updatedBy = "-";
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
     * Set id
     * @param integer $id
     * @return AEntity;
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Set updated
     */
    public function setUpdated()
    {
        $this->updated = new DateTime("now");

        return $this;
    }

    /**
     * Get updated
     *
     * @return DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set updated by
     * @param User|string|null $user
     * @return ATopEntity
     */
    public function setUpdatedBy($user)
    {
        $name = "-";
        if (is_a($user, User::class)) {
            $name = $user->getUsername();
        }
        $this->updatedBy = $name;

        return $this;
    }

    /**
     * Get updated by
     *
     * @return string
     */
    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }

    /**
     * Get created
     *
     * @return DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Get updated time, includes child objects
     * @return DateTime
     */
    public function getDeepUpdated()
    {
        return $this->updated;
    }

    /**
     * Get updated by, includes child objects
     * @return string
     */
    public function getDeepUpdatedBy()
    {
        return $this->updatedBy;
    }

    /**
     * @return User|null
     */
    public function getLockBy()
    {
        return null;
    }

    public static function reserveDependency(&$dependencies, $class, $id)
    {
        if (!array_key_exists("reservations", $dependencies))
            $dependencies["reservations"] = array();
        if (!array_key_exists($class, $dependencies["reservations"]))
            $dependencies["reservations"][$class] = array();
        if (!in_array($id, $dependencies["reservations"][$class]))
            array_push($dependencies["reservations"][$class], $id);
    }

    public static function isDependencyReserved($dependencies, $class, $id)
    {
        if (!array_key_exists("reservations", $dependencies))
            return false;
        if (!array_key_exists($class, $dependencies["reservations"]))
            return false;
        return in_array($id, $dependencies["reservations"][$class]);
    }

    public static function addDependency(&$dependencies, $serialized)
    {
        if (!array_key_exists("collection", $dependencies)) {
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
        if (!array_key_exists($class, $normalizedIdsMap)) {
            $normalizedIdsMap[$class] = array();
        }
        if (!array_key_exists($id, $normalizedIdsMap[$class])) {
            $normalizedIdsMap[$class][$id] = count($normalizedIdsMap[$class]) + 1;
        }
        return $normalizedIdsMap[$class][$id];
    }

    public abstract function getOwner();

    public abstract function getAccessibility();

    public abstract function hasAnyFromGroup($other_groups);
}
