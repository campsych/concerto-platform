<?php

namespace Concerto\PanelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Concerto\PanelBundle\Entity\Test;
use Concerto\PanelBundle\Entity\TestNode;
use Concerto\PanelBundle\Entity\TestNodePort;

/**
 * @ORM\Table
 * @ORM\Entity(repositoryClass="Concerto\PanelBundle\Repository\TestNodeConnectionRepository")
 */
class TestNodeConnection extends AEntity implements \JsonSerializable {

    /**
     * @ORM\ManyToOne(targetEntity="Test", inversedBy="nodesConnections")
     */
    private $flowTest;

    /**
     * @ORM\ManyToOne(targetEntity="TestNode", inversedBy="sourceForConnections")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $sourceNode;

    /**
     * @ORM\ManyToOne(targetEntity="TestNodePort", inversedBy="sourceForConnections")
     */
    private $sourcePort;

    /**
     * @ORM\ManyToOne(targetEntity="TestNode", inversedBy="destinationForConnections")
     */
    private $destinationNode;

    /**
     * @ORM\ManyToOne(targetEntity="TestNodePort", inversedBy="destinationForConnections")
     */
    private $destinationPort;

    /**
     * @var string
     * @ORM\Column(type="text")
     */
    private $returnFunction;
    
    /**
     *
     * @var boolean
     * @ORM\Column(type="boolean")
     */
    private $defaultReturnFunction;

    /**
     *
     * @var boolean
     * @ORM\Column(type="boolean")
     */
    private $automatic;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();

        $this->automatic = false;
        $this->returnFunction = "";
    }

    public function getOwner() {
        return $this->getFlowTest()->getOwner();
    }

    /**
     * Get return function
     *
     * @return string 
     */
    public function getReturnFunction() {
        return $this->returnFunction;
    }

    /**
     * Set return function
     *
     * @param string $returnFunction
     * @return TestNodeConnection
     */
    public function setReturnFunction($returnFunction) {
        $this->returnFunction = $returnFunction;

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
     * @return TestNodeConnection
     */
    public function setFlowTest($test) {
        $this->flowTest = $test;

        return $this;
    }

    /**
     * Get source node
     *
     * @return TestNode 
     */
    public function getSourceNode() {
        return $this->sourceNode;
    }

    /**
     * Set source node
     *
     * @param TestNode $node
     * @return TestNodeConnection
     */
    public function setSourceNode(TestNode $node) {
        $this->sourceNode = $node;

        return $this;
    }

    /**
     * Get destination node
     *
     * @return TestNode 
     */
    public function getDestinationNode() {
        return $this->destinationNode;
    }

    /**
     * Set destination node
     *
     * @param TestNode $node
     * @return TestNodeConnection
     */
    public function setDestinationNode(TestNode $node) {
        $this->destinationNode = $node;

        return $this;
    }

    /**
     * Get source port
     *
     * @return TestNodePort 
     */
    public function getSourcePort() {
        return $this->sourcePort;
    }

    /**
     * Set source port
     *
     * @param TestNodePort $port
     * @return TestNodeConnection
     */
    public function setSourcePort($port) {
        $this->sourcePort = $port;

        return $this;
    }

    /**
     * Get destination port
     *
     * @return TestNodePort 
     */
    public function getDestinationPort() {
        return $this->destinationPort;
    }

    /**
     * Set destination port
     *
     * @param TestNodePort $port
     * @return TestNodeConnection
     */
    public function setDestinationPort($port) {
        $this->destinationPort = $port;

        return $this;
    }

    /**
     * Is automatic?
     * 
     * @return boolean
     */
    public function isAutomatic() {
        return $this->automatic;
    }

    /**
     * Set automatic.
     * 
     * @param boolean $automatic
     */
    public function setAutomatic($automatic) {
        $this->automatic = $automatic;
    }
    
    /**
     * Returns true if connection has default return function.
     * 
     * @return boolean
     */
    public function hasDefaultReturnFunction() {
        return $this->defaultReturnFunction;
    }

    /**
     * Set whether connection has default return function.
     * 
     * @param boolean $defaultValue
     */
    public function setDefaultReturnFunction($default) {
        $this->defaultReturnFunction = $default;
    }
    
    public function getAccessibility() {
        return $this->getFlowTest()->getAccessibility();
    }

    public function hasAnyFromGroup($other_groups) {
        $groups = $this->getFlowTest()->getGroupsArray();
        foreach ($groups as $group) {
            foreach ($other_groups as $other_group) {
                if ($other_group == $group) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function getArrayHash($arr) {
        unset($arr["id"]);
        unset($arr["flowTest"]);
        unset($arr["sourceNode"]);
        unset($arr["sourcePort"]);
        $arr["sourcePortObject"] = $arr["sourcePortObject"] ? TestNodePort::getArrayHash($arr["sourcePortObject"]) : null;
        unset($arr["destinationNode"]);
        unset($arr["destinationPort"]);
        $arr["destinationPortObject"] = $arr["destinationPortObject"] ? TestNodePort::getArrayHash($arr["destinationPortObject"]) : null;

        $json = json_encode($arr);
        return sha1($json);
    }

    public function jsonSerialize(&$dependencies = array()) {
        return array(
            "class_name" => "TestNodeConnection",
            "id" => $this->id,
            "flowTest" => $this->flowTest->getId(),
            "sourceNode" => $this->sourceNode->getId(),
            "sourcePort" => $this->sourcePort ? $this->sourcePort->getId() : null,
            "sourcePortObject" => $this->sourcePort ? $this->sourcePort->jsonSerialize($dependencies) : null,
            "destinationNode" => $this->destinationNode->getId(),
            "destinationPort" => $this->destinationPort ? $this->destinationPort->getId() : null,
            "destinationPortObject" => $this->destinationPort ? $this->destinationPort->jsonSerialize($dependencies) : null,
            "returnFunction" => $this->returnFunction,
            "automatic" => $this->automatic ? "1" : "0",
            "defaultReturnFunction" => $this->defaultReturnFunction ? "1" : "0"
        );
    }

}
