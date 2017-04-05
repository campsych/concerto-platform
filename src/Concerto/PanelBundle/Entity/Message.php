<?php

namespace Concerto\PanelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;

/**
 * @ORM\Table
 * @ORM\Entity(repositoryClass="Concerto\PanelBundle\Repository\MessageRepository")
 */
class Message implements \JsonSerializable {

    const CATEGORY_SYSTEM = 0;
    const CATEGORY_TEST = 1;
    const CATEGORY_GLOBAL = 2;
    const CATEGORY_LOCAL = 3;
    const CATEGORY_CHANGELOG = 4;

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
    protected $time;

    /**
     *
     * @var integer
     * @ORM\Column(type="integer")
     */
    private $category;

    /**
     *
     * @var string
     * @ORM\Column(type="string")
     */
    private $subject;

    /**
     *
     * @var string
     * @ORM\Column(type="text")
     */
    private $message;

    public function __construct() {
        $this->time = new DateTime("now");
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
     * @return Message
     */
    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    /**
     * Set time
     *
     * @param DateTime $time
     * @return Message
     */
    public function setTime(DateTime $time) {
        $this->time = $time;

        return $this;
    }

    /**
     * Get time
     *
     * @return DateTime 
     */
    public function getTime() {
        return $this->time;
    }

    /**
     * Set category
     *
     * @param integer $category
     * @return Message
     */
    public function setCagegory($category) {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return integer 
     */
    public function getCategory() {
        return $this->category;
    }

    /**
     * Set subject
     *
     * @param string $subject
     * @return Message
     */
    public function setSubject($subject) {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject
     *
     * @return string 
     */
    public function getSubject() {
        return $this->subject;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return TestSessionLog
     */
    public function setMessage($message) {
        if ($message == null)
            $message = "";
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

    public function jsonSerialize(&$dependencies = array()) {
        return array(
            "class_name" => "Message",
            "id" => $this->getId(),
            "time" => $this->getTime()->format("Y-m-d H:i:s"),
            "category" => $this->getCategory(),
            "subject" => $this->getSubject(),
            "message" => $this->getMessage()
        );
    }

}
