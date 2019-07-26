<?php

namespace Concerto\PanelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Concerto\PanelBundle\Entity\User;

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
     *
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    protected $created;

    public function __construct()
    {
        $this->created = new DateTime("now");
        $this->updated = new DateTime("now");
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
     * Get created
     *
     * @return DateTime
     */
    public function getCreated()
    {
        return $this->created;
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
