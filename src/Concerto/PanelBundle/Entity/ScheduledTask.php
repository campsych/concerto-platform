<?php

namespace Concerto\PanelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;

/**
 * @ORM\Table
 * @ORM\Entity(repositoryClass="Concerto\PanelBundle\Repository\ScheduledTaskRepository")
 */
class ScheduledTask implements \JsonSerializable {

    const TYPE_PLATFORM_UPGRADE = 0;
    const TYPE_CONTENT_UPGRADE = 1;
    const TYPE_RESTORE_BACKUP = 2;
    const TYPE_BACKUP = 3;
    const TYPE_R_PACKAGE_INSTALL = 4;
    const STATUS_PENDING = 0;
    const STATUS_ONGOING = 1;
    const STATUS_COMPLETED = 2;
    const STATUS_FAILED = 3;
    const STATUS_CANCELED = 4;

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
    protected $created;

    /**
     * 
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    protected $updated;

    /**
     *
     * @var integer
     * @ORM\Column(type="integer")
     */
    private $type;

    /**
     *
     * @var integer
     * @ORM\Column(type="integer")
     */
    private $status;

    /**
     *
     * @var string
     * @ORM\Column(type="string")
     */
    private $description;

    /**
     *
     * @var string
     * @ORM\Column(type="text")
     */
    private $output;

    /**
     *
     * @var string
     * @ORM\Column(type="text")
     */
    private $info;

    public function __construct() {
        $this->created = new DateTime("now");
        $this->updated = new DateTime("now");
        $this->status = self::STATUS_PENDING;
        $this->output = "";
        $this->info = "";
        $this->description = "";
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
     * @return ScheduledTask
     */
    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    /**
     * Get created time
     *
     * @return DateTime 
     */
    public function getCreated() {
        return $this->created;
    }

    /**
     * Set updated time
     *
     * @return ScheduledTask
     */
    public function setUpdated() {
        $this->updated = new DateTime("now");

        return $this;
    }

    /**
     * Get updated time
     *
     * @return DateTime 
     */
    public function getUpdated() {
        return $this->updated;
    }

    /**
     * Set type
     *
     * @param integer $type
     * @return ScheduledTask
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
     * Set status
     *
     * @param integer $status
     * @return ScheduledTask
     */
    public function setStatus($status) {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return ScheduledTask
     */
    public function setDescription($description) {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Set output
     *
     * @param string $output
     * @return ScheduledTask
     */
    public function setOutput($output) {
        $this->output = $output;

        return $this;
    }

    /**
     * Get output
     *
     * @return string 
     */
    public function getOutput() {
        return $this->output;
    }

    public function appendOutput($output) {
        $this->output .= PHP_EOL . $output;
    }

    /**
     * Set info
     *
     * @param string $info
     * @return ScheduledTask
     */
    public function setInfo($info) {
        $this->info = $info;

        return $this;
    }

    /**
     * Get info
     *
     * @return string 
     */
    public function getInfo() {
        return $this->info;
    }

    public function jsonSerialize(&$dependencies = array()) {
        return array(
            "class_name" => "ScheduledTask",
            "id" => $this->getId(),
            "created" => $this->getCreated()->format("Y-m-d H:i:s"),
            "updated" => $this->getUpdated()->format("Y-m-d H:i:s"),
            "type" => $this->getType(),
            "status" => $this->getStatus(),
            "description" => $this->getDescription(),
            "output" => $this->getOutput()
        );
    }

}
