<?php

namespace Concerto\TestBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;

/**
 * @ORM\Table
 * @ORM\Entity(repositoryClass="Concerto\TestBundle\Repository\TestSessionCountRepository") 
 */
class TestSessionCount implements \JsonSerializable {

    /**
     * @var integer
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * 
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     *
     * @var integer
     * @ORM\Column(type="integer")
     */
    private $count;

    public function __construct() {
        $this->created = new DateTime("now");
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
     * Get created
     *
     * @return DateTime 
     */
    public function getCreated() {
        return $this->created;
    }

    /**
     * Set count
     *
     * @param integer $count
     */
    public function setCount($count) {
        $this->count = $count;

        return $this;
    }

    /**
     * Get count
     *
     * @return integer 
     */
    public function getCount() {
        return $this->count;
    }

    public function jsonSerialize() {
        $serialized = array(
            "x" => $this->created->getTimestamp(),
            "y" => $this->count
        );
        return $serialized;
    }

}
