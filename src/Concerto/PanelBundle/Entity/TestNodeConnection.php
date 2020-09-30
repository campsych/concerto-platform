<?php

namespace Concerto\PanelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Concerto\PanelBundle\Entity\Test;
use Concerto\PanelBundle\Entity\TestNode;
use Concerto\PanelBundle\Entity\TestNodePort;

/**
 * @ORM\Table
 * @ORM\Entity(repositoryClass="Concerto\PanelBundle\Repository\TestNodeConnectionRepository")
 * @ORM\HasLifecycleCallbacks
 */
class TestNodeConnection extends AEntity implements \JsonSerializable
{

    /**
     * @ORM\ManyToOne(targetEntity="Test", inversedBy="nodesConnections")
     */
    private $flowTest;

    /**
     * @ORM\ManyToOne(targetEntity="TestNode", inversedBy="sourceForConnections")
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
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->returnFunction = "";
    }

    public function getOwner()
    {
        return $this->getFlowTest()->getOwner();
    }

    /**
     * Get return function
     *
     * @return string
     */
    public function getReturnFunction()
    {
        return $this->returnFunction;
    }

    /**
     * Set return function
     *
     * @param string $returnFunction
     * @return TestNodeConnection
     */
    public function setReturnFunction($returnFunction)
    {
        $this->returnFunction = $returnFunction;

        return $this;
    }

    /**
     * Get flow test
     *
     * @return Test
     */
    public function getFlowTest()
    {
        return $this->flowTest;
    }

    /**
     * Set flow test
     *
     * @param Test $test
     * @return TestNodeConnection
     */
    public function setFlowTest($test)
    {
        $this->flowTest = $test;

        return $this;
    }

    /**
     * Get source node
     *
     * @return TestNode
     */
    public function getSourceNode()
    {
        return $this->sourceNode;
    }

    /**
     * Set source node
     *
     * @param TestNode $node
     * @return TestNodeConnection
     */
    public function setSourceNode(TestNode $node)
    {
        $this->sourceNode = $node;

        return $this;
    }

    /**
     * Get destination node
     *
     * @return TestNode
     */
    public function getDestinationNode()
    {
        return $this->destinationNode;
    }

    /**
     * Set destination node
     *
     * @param TestNode $node
     * @return TestNodeConnection
     */
    public function setDestinationNode(TestNode $node)
    {
        $this->destinationNode = $node;

        return $this;
    }

    /**
     * Get source port
     *
     * @return TestNodePort
     */
    public function getSourcePort()
    {
        return $this->sourcePort;
    }

    /**
     * Set source port
     *
     * @param TestNodePort $port
     * @return TestNodeConnection
     */
    public function setSourcePort($port)
    {
        $this->sourcePort = $port;

        return $this;
    }

    /**
     * Get destination port
     *
     * @return TestNodePort
     */
    public function getDestinationPort()
    {
        return $this->destinationPort;
    }

    /**
     * Set destination port
     *
     * @param TestNodePort $port
     * @return TestNodeConnection
     */
    public function setDestinationPort($port)
    {
        $this->destinationPort = $port;

        return $this;
    }

    /**
     * Returns true if connection has default return function.
     *
     * @return boolean
     */
    public function hasDefaultReturnFunction()
    {
        return $this->defaultReturnFunction;
    }

    /**
     * Set whether connection has default return function.
     *
     * @param boolean $default
     */
    public function setDefaultReturnFunction($default)
    {
        $this->defaultReturnFunction = $default;
    }

    public function getAccessibility()
    {
        return $this->getFlowTest()->getAccessibility();
    }

    public function hasAnyFromGroup($other_groups)
    {
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

    public function getTopEntity()
    {
        return $this->getFlowTest();
    }

    public function getLockBy()
    {
        return $this->getFlowTest()->getLockBy();
    }

    public function getEntityHash()
    {
        $json = json_encode(array(
            "sourcePort" => $this->getSourcePort() ? $this->getSourcePort()->getEntityHash() : null,
            "destinationPort" => $this->getDestinationPort() ? $this->getDestinationPort()->getEntityHash() : null,
            "returnFunction" => $this->getReturnFunction(),
            "defaultReturnFunction" => $this->hasDefaultReturnFunction()
        ));
        return sha1($json);
    }

    public function jsonSerialize(&$dependencies = array(), &$normalizedIdsMap = null)
    {
        $serialized = array(
            "class_name" => "TestNodeConnection",
            "id" => $this->id,
            "flowTest" => $this->flowTest->getId(),
            "sourceNode" => $this->sourceNode->getId(),
            "sourcePort" => $this->sourcePort ? $this->sourcePort->getId() : null,
            "destinationNode" => $this->destinationNode->getId(),
            "destinationPort" => $this->destinationPort ? $this->destinationPort->getId() : null,
            "returnFunction" => $this->returnFunction,
            "defaultReturnFunction" => $this->defaultReturnFunction ? "1" : "0"
        );

        if ($normalizedIdsMap !== null) {
            $serialized["id"] = self::normalizeId("TestNodeConnection", $serialized["id"], $normalizedIdsMap);
            $serialized["flowTest"] = self::normalizeId("Test", $serialized["flowTest"], $normalizedIdsMap);
            $serialized["sourceNode"] = self::normalizeId("TestNode", $serialized["sourceNode"], $normalizedIdsMap);
            $serialized["sourcePort"] = self::normalizeId("TestNodePort", $serialized["sourcePort"], $normalizedIdsMap);
            $serialized["destinationNode"] = self::normalizeId("TestNode", $serialized["destinationNode"], $normalizedIdsMap);
            $serialized["destinationPort"] = self::normalizeId("TestNodePort", $serialized["destinationPort"], $normalizedIdsMap);
        }

        return $serialized;
    }

    /** @ORM\PreRemove */
    public function preRemove()
    {
        $this->getSourceNode()->removeSourceForConnection($this);
        if ($this->getSourcePort()) $this->getSourcePort()->removeSourceForConnection($this);
        $this->getDestinationNode()->removeDestinationForConnection($this);
        if ($this->getDestinationPort()) $this->getDestinationPort()->removeDestinationForConnection($this);
        $this->getFlowTest()->removeNodeConnection($this);
    }

    /** @ORM\PrePersist */
    public function prePersist()
    {
        if (!$this->getSourceNode()->isSourceForConnection($this)) $this->getSourceNode()->addSourceForConnection($this);
        if ($this->getSourcePort() && !$this->getSourcePort()->isSourceForConnection($this)) $this->getSourcePort()->addSourceForConnection($this);
        if (!$this->getDestinationNode()->isDestinationForConnection($this)) $this->getDestinationNode()->addDestinationForConnection($this);
        if ($this->getDestinationPort() && !$this->getDestinationPort()->isDestinationForConnection($this)) $this->getDestinationPort()->removeDestinationForConnection($this);
        if (!$this->getFlowTest()->hasNodeConnection($this)) $this->getFlowTest()->addNodeConnection($this);
    }
}
