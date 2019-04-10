<?php

namespace Concerto\PanelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Concerto\PanelBundle\Entity\TestNode;
use Concerto\PanelBundle\Entity\TestVariable;
use Concerto\PanelBundle\Entity\TestNodeConnection;
use Concerto\PanelBundle\Entity\TestWizardParam;
use \Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table
 * @ORM\Entity(repositoryClass="Concerto\PanelBundle\Repository\TestNodePortRepository")
 * @UniqueEntity(fields={"node","type","name"}, message="validate.test.ports.unique")
 */
class TestNodePort extends AEntity implements \JsonSerializable
{

    /**
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @ORM\ManyToOne(targetEntity="TestNode", inversedBy="ports")
     */
    private $node;

    /**
     * @ORM\ManyToOne(targetEntity="TestVariable", inversedBy="ports")
     */
    private $variable;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    private $value;

    /**
     * @ORM\OneToMany(targetEntity="TestNodeConnection", mappedBy="sourcePort", cascade={"remove"}, orphanRemoval=true)
     */
    private $sourceForConnections;

    /**
     * @ORM\OneToMany(targetEntity="TestNodeConnection", mappedBy="destinationPort", cascade={"remove"}, orphanRemoval=true)
     */
    private $destinationForConnections;

    /**
     *
     * @var boolean
     * @ORM\Column(type="boolean")
     */
    private $string;

    /**
     *
     * @var boolean
     * @ORM\Column(type="boolean")
     */
    private $defaultValue;

    /**
     *
     * @var boolean
     * @ORM\Column(type="boolean")
     */
    private $dynamic;

    /**
     *
     * @var boolean
     * @ORM\Column(type="boolean")
     */
    private $exposed;

    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    private $type;

    /**
     * @var string
     * @ORM\Column(type="string", length=64)
     * @Assert\Length(min="1", max="64", minMessage="validate.test.ports.name.min", maxMessage="validate.test.ports.name.max")
     * @Assert\NotBlank(message="validate.test.ports.name.blank")
     * @Assert\Regex("/^\.?[a-zA-Z][a-zA-Z0-9_]*(?<!_)$/", message="validate.test.ports.name.incorrect")
     */
    private $name;

    /**
     *
     * @var boolean
     * @ORM\Column(type="boolean")
     */
    private $pointer;

    /**
     * @var string
     * @ORM\Column(type="string", length=64)
     */
    private $pointerVariable;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->string = true;
        $this->defaultValue = true;
        $this->dynamic = false;
        $this->exposed = false;
        $this->sourceForConnections = new ArrayCollection();
        $this->destinationForConnections = new ArrayCollection();
        $this->pointer = false;
        $this->pointerVariable = "";
    }

    public function getOwner()
    {
        return $this->getNode()->getFlowTest()->getOwner();
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return TestNodePort
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Set node
     *
     * @param TestNode $node
     * @return TestNodePort
     */
    public function setNode($node)
    {
        $this->node = $node;

        return $this;
    }

    /**
     * Get node
     *
     * @return TestNode
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * Set variable
     *
     * @param TestVariable $var
     * @return TestNodePort
     */
    public function setVariable($var)
    {
        $this->variable = $var;

        return $this;
    }

    /**
     * Get variable
     *
     * @return TestVariable
     */
    public function getVariable()
    {
        return $this->variable;
    }

    /**
     * Add source for connection
     *
     * @param TestNodeConnection $connection
     * @return TestNode
     */
    public function addSourceForConnection(TestNodeConnection $connection)
    {
        $this->sourceForConnections[] = $connection;

        return $this;
    }

    /**
     * Remove source for connection
     *
     * @param TestNodeConnection $connection
     */
    public function removeSourceForConnection(TestNodeConnection $connection)
    {
        $this->sourceForConnections->removeElement($connection);
    }

    /**
     * Get source for connections
     *
     * @return Collection
     */
    public function getSourceForConnections()
    {
        return $this->sourceForConnections;
    }

    /**
     * Add destination for connection
     *
     * @param TestNodeConnection $connection
     * @return TestNode
     */
    public function addDestinationForConnection(TestNodeConnection $connection)
    {
        $this->destinationForConnections[] = $connection;

        return $this;
    }

    /**
     * Remove destination for connection
     *
     * @param TestNodeConnection $connection
     */
    public function removeDestinationForConnection(TestNodeConnection $connection)
    {
        $this->destinationForConnections->removeElement($connection);
    }

    /**
     * Get destination for connections
     *
     * @return Collection
     */
    public function getDestinationForConnections()
    {
        return $this->destinationForConnections;
    }

    /**
     * Returns if a port value should be treated as string.
     *
     * @return boolean
     */
    public function isString()
    {
        return $this->string;
    }

    /**
     * Set if port value should be treated as string.
     *
     * @param boolean $string
     */
    public function setString($string)
    {
        $this->string = $string;
    }

    /**
     * Returns true if port has default value.
     *
     * @return boolean
     */
    public function hasDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * Set whether port has default value.
     *
     * @param boolean $defaultValue
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;
    }

    /**
     * Returns true if port is dynamic.
     *
     * @return boolean
     */
    public function isDynamic()
    {
        return $this->dynamic;
    }

    /**
     * Set whether port is dynamic.
     *
     * @param boolean $dynamic
     * @return TestNodePort
     */
    public function setDynamic($dynamic)
    {
        $this->dynamic = $dynamic;
        return $this;
    }

    /**
     * Returns true if port is exposed.
     *
     * @return boolean
     */
    public function isExposed()
    {
        return $this->exposed;
    }

    /**
     * Set whether port is exposed.
     *
     * @param boolean $exposed
     * @return TestNodePort
     */
    public function setExposed($exposed)
    {
        $this->exposed = $exposed;
        return $this;
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
     * @return TestNodePort
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return TestNodePort
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Returns true if port is pointer.
     *
     * @return boolean
     */
    public function isPointer()
    {
        return $this->pointer;
    }

    /**
     * Set whether port is pointer.
     *
     * @param boolean $pointer
     * @return TestNodePort
     */
    public function setPointer($pointer)
    {
        $this->pointer = $pointer;
        return $this;
    }

    /**
     * Get pointer variable name
     *
     * @return string
     */
    public function getPointerVariable()
    {
        return $this->pointerVariable;
    }

    /**
     * Set pointer variable
     *
     * @param string $name
     * @return TestNodePort
     */
    public function setPointerVariable($name)
    {
        $this->pointerVariable = $name;

        return $this;
    }

    public function getAccessibility()
    {
        return $this->getNode()->getFlowTest()->getAccessibility();
    }

    public function hasAnyFromGroup($other_groups)
    {
        $groups = $this->getNode()->getFlowTest()->getGroupsArray();
        foreach ($groups as $group) {
            foreach ($other_groups as $other_group) {
                if ($other_group == $group) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function getArrayHash($arr)
    {
        unset($arr["id"]);
        unset($arr["node"]);
        unset($arr["variable"]);
        $arr["variableObject"] = $arr["variableObject"] ? TestVariable::getArrayHash($arr["variableObject"]) : null;

        $json = json_encode($arr);
        return sha1($json);
    }

    public function jsonSerialize(&$dependencies = array())
    {
        if ($this->variable) {
            $wizard = $this->variable->getTest()->getSourceWizard();
            if ($wizard) {
                foreach ($wizard->getParams() as $param) {
                    if ($this->variable->getParentVariable() == null)
                        continue;
                    if ($param->getVariable()->getId() == $this->variable->getParentVariable()->getId()) {
                        TestWizardParam::getParamValueDependencies($this->value, $param->getDefinition(), $param->getType(), $dependencies);
                        break;
                    }
                }
            }
        }

        return array(
            "class_name" => "TestNodePort",
            "id" => $this->id,
            "value" => $this->value,
            "node" => $this->node->getId(),
            "variable" => $this->variable ? $this->variable->getId() : null,
            "variableObject" => $this->variable ? $this->variable->jsonSerialize($dependencies) : null,
            "string" => $this->string ? "1" : "0",
            "defaultValue" => $this->defaultValue ? "1" : "0",
            "dynamic" => $this->dynamic ? "1" : "0",
            "type" => $this->type,
            "exposed" => $this->exposed ? "1" : "0",
            "name" => $this->getName(),
            "pointer" => $this->pointer ? "1" : "0",
            "pointerVariable" => $this->pointerVariable
        );
    }

}
