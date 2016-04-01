<?php

namespace Concerto\PanelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Concerto\PanelBundle\Entity\User;

abstract class AEntity {

    const ACCESS_PUBLIC = 0;
    const ACCESS_PROTECTED = 1;
    const ACCESS_ARCHIVED = 2;

    /**
     * @var integer
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     *
     * @var integer
     * @ORM\Column(type="integer")
     */
    protected $globalId;

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

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    protected $updatedBy;

    /**
     *
     * @var tags
     * @ORM\Column(type="string")
     */
    protected $tags;

    public function __construct() {
        $this->tags = "";
        $this->globalId = 0;
        $this->created = new DateTime("now");
        $this->updated = new DateTime("now");
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set id
     * @param integer $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * Set updated
     */
    public function setUpdated() {
        $this->updated = new DateTime("now");

        return $this;
    }

    /**
     * Get updated
     *
     * @return DateTime 
     */
    public function getUpdated() {
        return $this->updated;
    }

    /**
     * Set updated by 
     * @param User $user
     */
    public function setUpdatedBy(User $user) {
        $this->updatedBy = $user;

        return $this;
    }

    /**
     * Get updated by
     *
     * @return User 
     */
    public function getUpdatedBy() {
        return $this->updatedBy;
    }

    /**
     * Get created
     *
     * @return DateTime 
     */
    public function getCreated() {
        return $this->created;
    }

    /**
     * Set global id
     *
     * @param integer $id
     */
    public function setGlobalId($id) {
        $this->globalId = $id;

        return $this;
    }

    /**
     * Get global id
     *
     * @return integer 
     */
    public function getGlobalId() {
        return $this->globalId;
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

}
