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
 * @ORM\HasLifecycleCallbacks
 */
class TestNode extends AEntity implements \JsonSerializable
{

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
     * @ORM\OneToMany(targetEntity="TestNodeConnection", mappedBy="sourceNode", cascade={"remove"}, orphanRemoval=true)
     */
    private $sourceForConnections;

    /**
     * @ORM\OneToMany(targetEntity="TestNodeConnection", mappedBy="destinationNode", cascade={"remove"}, orphanRemoval=true)
     */
    private $destinationForConnections;

    /**
     * @ORM\OneToMany(targetEntity="TestNodePort", mappedBy="node", cascade={"remove"}, orphanRemoval=true)
     */
    private $ports;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->title = "";
        $this->type = 0;
        $this->posX = 0;
        $this->posY = 0;
        $this->ports = new ArrayCollection();
        $this->sourceForConnections = new ArrayCollection();
        $this->destinationForConnections = new ArrayCollection();
    }

    /**
     * Set title
     *
     * @param string $title
     * @return TestNode
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    public function getOwner()
    {
        return $this->getFlowTest()->getOwner();
    }

    /**
     * Get type
     *
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set type
     *
     * @param integer $type
     * @return TestNode
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get position X
     *
     * @return integer
     */
    public function getPosX()
    {
        return $this->posX;
    }

    /**
     * Set position X
     *
     * @param integer $posX
     * @return TestNode
     */
    public function setPosX($posX)
    {
        $this->posX = $posX;

        return $this;
    }

    /**
     * Get position Y
     *
     * @return integer
     */
    public function getPosY()
    {
        return $this->posY;
    }

    /**
     * Set position Y
     *
     * @param integer $posY
     * @return TestNode
     */
    public function setPosY($posY)
    {
        $this->posY = $posY;

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
     * @return TestNode
     */
    public function setFlowTest($test)
    {
        $this->flowTest = $test;

        return $this;
    }

    /**
     * Get source test
     *
     * @return Test
     */
    public function getSourceTest()
    {
        return $this->sourceTest;
    }

    /**
     * Set source test
     *
     * @param Test $test
     * @return TestNode
     */
    public function setSourceTest($test)
    {
        $this->sourceTest = $test;

        return $this;
    }

    /**
     * Add port
     *
     * @param TestNodePort $port
     * @return TestNode
     */
    public function addPort(TestNodePort $port)
    {
        $this->ports[] = $port;

        return $this;
    }

    /**
     * Remove port
     *
     * @param TestNodePort $port
     */
    public function removePort(TestNodePort $port)
    {
        $this->ports->removeElement($port);
    }

    /**
     * Get ports
     *
     * @return array
     */
    public function getPorts()
    {
        return $this->ports->toArray();
    }

    public function hasPort(TestNodePort $port)
    {
        return $this->ports->contains($port);
    }

    public function getPortByVariable(TestVariable $variable)
    {
        foreach ($this->getPorts() as $port) {
            if ($port->getVariable() === $variable) return $port;
        }
        return null;
    }

    public function getPortsByNameType($name, $type)
    {
        return $this->ports->filter(function (TestNodePort $port) use ($name, $type) {
            return $port->getName() === $name && $port->getType() == $type;
        })->toArray();
    }

    public function getAccessibility()
    {
        return $this->getFlowTest()->getAccessibility();
    }

    public function getTopEntity()
    {
        return $this->getFlowTest();
    }

    public function getLockBy()
    {
        return $this->getFlowTest()->getLockBy();
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

    public function getSourceForConnections()
    {
        return $this->sourceForConnections->toArray();
    }

    public function removeSourceForConnection(TestNodeConnection $connection)
    {
        $this->sourceForConnections->removeElement($connection);
        return $this;
    }

    public function addSourceForConnection(TestNodeConnection $connection)
    {
        $this->sourceForConnections->add($connection);
        return $this;
    }

    public function isSourceForConnection(TestNodeConnection $connection)
    {
        return $this->sourceForConnections->contains($connection);
    }

    public function getDestinationForConnections()
    {
        return $this->destinationForConnections->toArray();
    }

    public function removeDestinationForConnection(TestNodeConnection $connection)
    {
        $this->destinationForConnections->removeElement($connection);
        return $this;
    }

    public function addDestinationForConnection(TestNodeConnection $connection)
    {
        $this->destinationForConnections->add($connection);
        return $this;
    }

    public function isDestinationForConnection(TestNodeConnection $connection)
    {
        return $this->destinationForConnections->contains($connection);
    }

    public function getEntityHash()
    {
        $json = json_encode(array(
            "title" => $this->getTitle(),
            "type" => $this->getType(),
            "posX" => $this->getPosX(),
            "posY" => $this->getPosY(),
            "sourceTestName" => $this->sourceTest->getName(),
            "ports" => AEntity::getEntityCollectionHash($this->getPorts())
        ));
        return sha1($json);
    }

    public function __toString()
    {
        return "TestNode (#" . $this->getId() . ", title:" . ($this->getTitle() ? $this->getTitle() : $this->getSourceTest()->getName()) . ")";
    }

    public function jsonSerialize(&$dependencies = array(), &$normalizedIdsMap = null)
    {
        if ($this->sourceTest != null)
            $this->sourceTest->jsonSerialize($dependencies, $normalizedIdsMap);

        $serialized = array(
            "class_name" => "TestNode",
            "id" => $this->id,
            "title" => $this->title,
            "type" => $this->type,
            "posX" => $this->posX,
            "posY" => $this->posY,
            "flowTest" => $this->flowTest->getId(),
            "sourceTest" => $this->sourceTest->getId(),
            "sourceTestName" => $this->sourceTest->getName(),
            "sourceTestDescription" => $this->sourceTest->getDescription(),
            "ports" => self::jsonSerializeArray($this->getPorts(), $dependencies, $normalizedIdsMap),
        );

        if ($normalizedIdsMap !== null) {
            $serialized["id"] = self::normalizeId("TestNode", $serialized["id"], $normalizedIdsMap);
            $serialized["flowTest"] = self::normalizeId("Test", $serialized["flowTest"], $normalizedIdsMap);
            $serialized["sourceTest"] = self::normalizeId("Test", $serialized["sourceTest"], $normalizedIdsMap);
        }

        return $serialized;
    }

    /** @ORM\PreRemove */
    public function preRemove()
    {
        $this->getFlowTest()->removeNode($this);
        $this->getSourceTest()->removeSourceForNodes($this);
    }

    /** @ORM\PrePersist */
    public function prePersist()
    {
        if (!$this->getFlowTest()->hasNode($this)) $this->getFlowTest()->addNode($this);
        if (!$this->getSourceTest()->isSourceForNodes($this)) $this->getSourceTest()->addSourceForNodes($this);
    }
}
