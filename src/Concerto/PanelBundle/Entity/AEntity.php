<?php

namespace Concerto\PanelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Concerto\PanelBundle\Entity\User;

abstract class AEntity {

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

    public static function isInProcessedArray($processed, $class, $id) {
        if (!array_key_exists($class, $processed))
            return false;
        return in_array($id, $processed[$class]);
    }

    public static function jsonSerializeArray($array, &$processed = array()) {
        $result = array();
        foreach ($array as $ent) {
            array_push($result, $ent->jsonSerialize($processed));
        }
        return $result;
    }

    public static function addToProcessedArray(&$processed, $class, $id) {
        if (!array_key_exists($class, $processed)) {
            $processed[$class] = array();
        }
        if (!in_array($id, $processed[$class])) {
            array_push($processed[$class], $id);
        }
    }

    public abstract function getOwner();
}
