<?php

namespace Concerto\PanelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Concerto\PanelBundle\Entity\Test;
use Concerto\PanelBundle\Entity\TestNodePort;
use Concerto\PanelBundle\Entity\TestNodeConnection;
use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Table
 * @ORM\Entity(repositoryClass="Concerto\PanelBundle\Repository\TestNodeRepository")
 */
class TestNode extends AEntity implements \JsonSerializable {
    
    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $title;

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
    private $posX;
    
    /**
     *
     * @var integer
     * @ORM\Column(type="integer")
     */
    private $posY;

    /**
     * @ORM\ManyToOne(targetEntity="Test", inversedBy="nodes")
     */
    private $flowTest;
    
    /**
     * @ORM\ManyToOne(targetEntity="Test", inversedBy="sourceForNodes")
     */
    private $sourceTest;
    
    /**
     * @ORM\OneToMany(targetEntity="TestNodePort", mappedBy="node", cascade={"remove"})
     */
    private $ports;
    
    /**
     * @ORM\OneToMany(targetEntity="TestNodeConnection", mappedBy="sourceNode", cascade={"remove"})
     */
    private $sourceForConnections;
    
    /**
     * @ORM\OneToMany(targetEntity="TestNodeConnection", mappedBy="destinationNode", cascade={"remove"})
     */
    private $destinationForConnections;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        $this->title = "";
        $this->type = 0;
        $this->posX = 0;
        $this->posY = 0;
        $this->ports = new ArrayCollection();
    }
    
     /**
     * Set title
     *
     * @param string $title
     * @return TestNode
     */
    public function setTitle($title) {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle() {
        return $this->title;
    }
    
    public function getOwner() {
        return $this->getFlowTest()->getOwner();
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
     * Set type
     *
     * @param integer $type
     * @return TestNode
     */
    public function setType($type) {
        $this->type = $type;

        return $this;
    }

    /**
     * Get position X
     *
     * @return integer 
     */
    public function getPosX() {
        return $this->posX;
    }

    /**
     * Set position X
     *
     * @param integer $posX
     * @return TestNode
     */
    public function setPosX($posX) {
        $this->posX = $posX;

        return $this;
    }
    
    /**
     * Get position Y
     *
     * @return integer 
     */
    public function getPosY() {
        return $this->posY;
    }

    /**
     * Set position Y
     *
     * @param integer $posY
     * @return TestNode
     */
    public function setPosY($posY) {
        $this->posY = $posY;

        return $this;
    }

    /**
     * Get flow test
     *
     * @return Test 
     */
    public function getFlowTest() {
        return $this->flowTest;
    }

    /**
     * Set flow test
     *
     * @param Test $test
     * @return TestNode
     */
    public function setFlowTest($test) {
        $this->flowTest = $test;

        return $this;
    }
    
    /**
     * Get source test
     *
     * @return Test 
     */
    public function getSourceTest() {
        return $this->sourceTest;
    }

    /**
     * Set source test
     *
     * @param Test $test
     * @return TestNode
     */
    public function setSourceTest($test) {
        $this->sourceTest = $test;

        return $this;
    }
    
    /**
     * Add port
     *
     * @param TestNodePort $port
     * @return TestNode
     */
    public function addPort(TestNodePort $port) {
        $this->ports[] = $port;

        return $this;
    }

    /**
     * Remove port
     *
     * @param TestNodePort $port
     */
    public function removePort(TestNodePort $port) {
        $this->ports->removeElement($port);
    }

    /**
     * Get ports
     *
     * @return Collection 
     */
    public function getPorts() {
        return $this->ports;
    }
    
    /**
     * Add source for connection
     *
     * @param TestNodeConnection $connection
     * @return TestNode
     */
    public function addSourceForConnection(TestNodeConnection $connection) {
        $this->sourceForConnections[] = $connection;

        return $this;
    }

    /**
     * Remove source for connection
     *
     * @param TestNodeConnection $connection
     */
    public function removeSourceForConnection(TestNodeConnection $connection) {
        $this->sourceForConnections->removeElement($connection);
    }

    /**
     * Get source for connections
     *
     * @return Collection 
     */
    public function getSourceForConnections() {
        return $this->sourceForConnections;
    }
    
    /**
     * Add destination for connection
     *
     * @param TestNodeConnection $connection
     * @return TestNode
     */
    public function addDestinationForConnection(TestNodeConnection $connection) {
        $this->destinationForConnections[] = $connection;

        return $this;
    }

    /**
     * Remove destination for connection
     *
     * @param TestNodeConnection $connection
     */
    public function removeDestinationForConnection(TestNodeConnection $connection) {
        $this->destinationForConnections->removeElement($connection);
    }

    /**
     * Get destination for connections
     *
     * @return Collection 
     */
    public function getDestinationForConnections() {
        return $this->destinationForConnections;
    }

    public function jsonSerialize() {
        return array(
            "class_name" => "TestNode",
            "id" => $this->id,
            "title" => $this->title,
            "type" => $this->type,
            "posX" => $this->posX,
            "posY" => $this->posY,
            "flowTest" => $this->flowTest->getId(),
            "sourceTest" => $this->sourceTest->getId(),
            "sourceTestObject" => $this->type == 0 ? $this->sourceTest : null,
            "sourceTestName" => $this->sourceTest->getName(),
            "sourceTestDescription" => $this->sourceTest->getDescription(),
            "ports" => $this->getPorts()->toArray(),
        );
    }
}
