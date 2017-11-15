<?php

namespace Concerto\PanelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Concerto\PanelBundle\Entity\Test;

/**
 * @ORM\Table
 * @ORM\Entity(repositoryClass="Concerto\PanelBundle\Repository\TestSessionLogRepository")
 */
class TestSessionLog extends AEntity implements \JsonSerializable {

    const TYPE_SYSTEM = 2;
    const TYPE_R = 1;
    const TYPE_JS = 0;

    /**
     *
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $ip;

    /**
     *
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $browser;

    /**
     *
     * @var string
     * @ORM\Column(type="text")
     */
    private $message;

    /**
     *
     * @var integer
     * @ORM\Column(type="integer")
     */
    private $type;

    /**
     * @ORM\ManyToOne(targetEntity="Test", inversedBy="logs")
     */
    private $test;

    public function __construct() {
        parent::__construct();
        
        $this->message = "";
    }
    
    public function getOwner() {
        return $this->getTest()->getOwner();
    }

    /**
     * Set ip
     *
     * @param string $ip
     * @return TestSessionLog
     */
    public function setIp($ip) {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get ip
     *
     * @return string 
     */
    public function getIp() {
        return $this->ip;
    }

    /**
     * Set browser
     *
     * @param string $browser
     * @return TestSessionLog
     */
    public function setBrowser($browser) {
        $this->browser = $browser;

        return $this;
    }

    /**
     * Get browser
     *
     * @return string 
     */
    public function getBrowser() {
        return $this->browser;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return TestSessionLog
     */
    public function setMessage($message) {
        if($message == null) $message = "";
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string 
     */
    public function getMessage() {
        return $this->message;
    }

    /**
     * Set type
     *
     * @param integer $type
     * @return TestSessionLog
     */
    public function setType($type) {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer 
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Set test
     *
     * @param Test $test
     * @return TestSessionLog
     */
    public function setTest(Test $test = null) {
        $this->test = $test;

        return $this;
    }

    /**
     * Get test
     *
     * @return Test 
     */
    public function getTest() {
        return $this->test;
    }
    
    public function getAccessibility() {
        return $this->getTest()->getAccessibility();
    }

    public function hasAnyFromGroup($other_groups) {
        $groups = $this->getTest()->getGroupsArray();
        foreach ($groups as $group) {
            foreach ($other_groups as $other_group) {
                if ($other_group == $group) {
                    return true;
                }
            }
        }
        return false;
    }

    public function jsonSerialize(&$dependencies = array()) {
        return array(
            "class_name" => "TestSessionLog",
            "id" => $this->getId(),
            "created" => $this->getCreated()->format("Y-m-d H:i:s"),
            "browser" => $this->getBrowser(),
            "ip" => $this->getIp(),
            "message" => $this->getMessage(),
            "type" => $this->getType(),
            "test_id" => $this->getTest()->getId()
        );
    }

}
