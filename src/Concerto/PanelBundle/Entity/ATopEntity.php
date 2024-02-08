<?php

namespace Concerto\PanelBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

abstract class ATopEntity extends AEntity
{

    const ACCESS_PUBLIC = 2;
    const ACCESS_GROUP = 1;
    const ACCESS_PRIVATE = 0;

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

    /**
     *
     * @var integer
     * @ORM\Column(type="integer")
     */
    protected $accessibility;

    /**
     *
     * @var string groups
     * @ORM\Column(name="objectGroups", type="string")
     */
    protected $groups;

    /**
     *
     * @var boolean
     * @ORM\Column(type="boolean")
     */
    protected $archived;

    /**
     *
     * @var boolean
     * @ORM\Column(type="boolean")
     */
    protected $starterContent;

    /**
     *
     * @var string
     * @ORM\Column(type="string")
     */
    protected $tags;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    protected $directLockBy;

    public function __construct()
    {
        parent::__construct();

        $this->created = new DateTime("now");
        $this->updated = new DateTime("now");
        $this->updatedBy = "-";
        $this->accessibility = self::ACCESS_PRIVATE;
        $this->groups = "";
        $this->archived = false;
        $this->starterContent = false;
        $this->tags = "";
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
     * Set updated
     *
     * @param DateTime $updated
     * @return ATopEntity
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
        return $this;
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
     * Set accessibility
     *
     * @param integer $access
     * @return ATopEntity
     */
    public function setAccessibility($access)
    {
        $this->accessibility = $access;

        return $this;
    }

    /**
     * Get accessibility
     *
     * @return integer
     */
    public function getAccessibility()
    {
        return $this->accessibility;
    }

    /**
     * Set archived
     *
     * @param boolean $archived
     * @return ATopEntity
     */
    public function setArchived($archived)
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * Is archived
     *
     * @return boolean
     */
    public function isArchived()
    {
        return $this->archived;
    }

    /**
     * Set starter content
     *
     * @param boolean $starterContent
     * @return ATopEntity
     */
    public function setStarterContent($starterContent)
    {
        $this->starterContent = $starterContent;

        return $this;
    }

    /**
     * Is starter content
     *
     * @return boolean
     */
    public function isStarterContent()
    {
        return $this->starterContent;
    }

    /**
     * Set tags
     *
     * @param string $tags
     * @return ATopEntity
     */
    public function setTags($tags)
    {
        $this->tags = trim($tags);

        return $this;
    }

    /**
     * Get tags
     *
     * @return string
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Get tags array
     *
     * @return string
     */
    public function getTagsArray()
    {
        $result = array();
        $tags = explode(" ", $this->tags);
        for ($i = 0; $i < count($tags); $i++) {
            if ($tags[$i]) {
                array_push($result, ucwords(str_replace("_", " ", $tags[$i])));
            }
        }
        return $result;
    }

    /**
     * Set groups
     *
     * @param string $groups
     * @return ATopEntity
     */
    public function setGroups($groups)
    {
        $this->groups = trim($groups);

        return $this;
    }

    /**
     * Get groups
     *
     * @return string
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Get groups array
     *
     * @return array
     */
    public function getGroupsArray()
    {
        $groups = explode(",", $this->groups);
        $result = array();
        foreach ($groups as $group) {
            $g = trim($group);
            if ($g) {
                array_push($result, $g);
            }
        }
        return $result;
    }

    /**
     * Has group
     *
     * @param string $group
     * @return boolean
     */
    public function hasGroup($group)
    {
        if (!trim($group)) {
            return false;
        }
        $groups = $this->getGroupsArray();
        foreach ($groups as $g) {
            if ($g === $group) {
                return true;
            }
        }
        return false;
    }

    /**
     * Has any of the group
     *
     * @param array $other_groups
     * @return boolean
     */
    public function hasAnyFromGroup($other_groups)
    {
        $groups = $this->getGroupsArray();
        foreach ($groups as $group) {
            foreach ($other_groups as $other_group) {
                if ($other_group == $group) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Set direct lock by
     * @param User $user
     * @return ATopEntity
     */
    public function setDirectLockBy($user)
    {
        $this->directLockBy = $user;

        return $this;
    }

    /**
     * Get direct lock by
     * @return User
     */
    public function getDirectLockBy()
    {
        return $this->directLockBy;
    }

    public function getLockBy()
    {
        return $this->directLockBy;
    }
}
