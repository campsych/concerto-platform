<?php

namespace Concerto\PanelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

abstract class ATopEntity extends AEntity
{

    const ACCESS_PUBLIC = 2;
    const ACCESS_GROUP = 1;
    const ACCESS_PRIVATE = 0;

    /**
     *
     * @var integer
     * @ORM\Column(type="integer")
     */
    protected $accessibility;

    /**
     *
     * @var groups
     * @ORM\Column(type="string")
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
     * @var string
     * @ORM\Column(type="string")
     */
    protected $updatedBy;

    /**
     *
     * @var string
     * @ORM\Column(type="string")
     */
    protected $tags;

    public function __construct()
    {
        parent::__construct();

        $this->accessibility = self::ACCESS_PRIVATE;
        $this->groups = "";
        $this->archived = false;
        $this->starterContent = false;
        $this->tags = "";
        $this->updatedBy = "";
    }

    /**
     * Set accessibility
     *
     * @param integer $access
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
     * Set updated by
     * @param string $user
     */
    public function setUpdatedBy($user) {
        $this->updatedBy = $user;

        return $this;
    }

    /**
     * Get updated by
     *
     * @return string
     */
    public function getUpdatedBy() {
        return $this->updatedBy;
    }

    /**
     * Set tags
     *
     * @param string $tags
     */
    public function setTags($tags) {
        $this->tags = trim($tags);

        return $this;
    }

    /**
     * Get tags
     *
     * @return string
     */
    public function getTags() {
        return $this->tags;
    }

    /**
     * Get tags array
     *
     * @return string
     */
    public function getTagsArray() {
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
}
