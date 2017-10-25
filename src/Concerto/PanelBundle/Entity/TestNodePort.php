<?php

namespace Concerto\PanelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Concerto\PanelBundle\Entity\TestNode;
use Concerto\PanelBundle\Entity\TestVariable;
use Concerto\PanelBundle\Entity\TestNodeConnection;
use Concerto\PanelBundle\Entity\TestWizardParam;
use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Table
 * @ORM\Entity(repositoryClass="Concerto\PanelBundle\Repository\TestNodePortRepository")
 */
class TestNodePort extends AEntity implements \JsonSerializable {

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
     * Constructor
     */
    public function __construct() {
        parent::__construct();

        $this->string = true;
        $this->defaultValue = true;
        $this->sourceForConnections = new ArrayCollection();
        $this->destinationForConnections = new ArrayCollection();
    }

    public function getOwner() {
        return $this->getNode()->getFlowTest()->getOwner();
    }

    /**
     * Get value
     *
     * @return string 
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return TestNodePort
     */
    public function setValue($value) {
        $this->value = $value;

        return $this;
    }

    /**
     * Set node
     *
     * @param TestNode $node
     * @return TestNodePort
     */
    public function setNode($node) {
        $this->node = $node;

        return $this;
    }

    /**
     * Get node
     *
     * @return TestNode
     */
    public function getNode() {
        return $this->node;
    }

    /**
     * Set variable
     *
     * @param TestVariable $var
     * @return TestNodePort
     */
    public function setVariable($var) {
        $this->variable = $var;

        return $this;
    }

    /**
     * Get variable
     *
     * @return TestVariable
     */
    public function getVariable() {
        return $this->variable;
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

    /**
     * Returns if a port value should be treated as string.
     * 
     * @return boolean
     */
    public function isString() {
        return $this->string;
    }

    /**
     * Set if port value should be treated as string.
     * 
     * @param boolean $string
     */
    public function setString($string) {
        $this->string = $string;
    }

    /**
     * Returns true if port has default value.
     * 
     * @return boolean
     */
    public function hasDefaultValue() {
        return $this->defaultValue;
    }

    /**
     * Set whether port has default value.
     * 
     * @param boolean $defaultValue
     */
    public function setDefaultValue($defaultValue) {
        $this->defaultValue = $defaultValue;
    }
    
    public function getAccessibility() {
        return $this->getNode()->getFlowTest()->getAccessibility();
    }

    public function hasAnyFromGroup($other_groups) {
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

    public static function getArrayHash($arr) {
        unset($arr["id"]);
        unset($arr["node"]);
        unset($arr["variable"]);
        $arr["variableObject"] = $arr["variableObject"] ? TestVariable::getArrayHash($arr["variableObject"]) : null;

        $json = json_encode($arr);
        return sha1($json);
    }

    public function jsonSerialize(&$dependencies = array()) {
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

        return array(
            "class_name" => "TestNodePort",
            "id" => $this->id,
            "value" => $this->value,
            "node" => $this->node->getId(),
            "variable" => $this->variable->getId(),
            "variableObject" => $this->variable->jsonSerialize($dependencies),
            "string" => $this->string ? "1" : "0",
            "defaultValue" => $this->defaultValue ? "1" : "0"
        );
    }

}
