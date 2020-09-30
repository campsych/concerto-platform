<?php

namespace Concerto\PanelBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Table
 * @ORM\Entity(repositoryClass="Concerto\PanelBundle\Repository\TestVariableRepository")
 * @UniqueEntity(fields={"name","type","test"}, message="validate.test.variables.unique")
 * @ORM\HasLifecycleCallbacks
 */
class TestVariable extends AEntity implements \JsonSerializable
{

    /**
     * @var string
     * @ORM\Column(type="string", length=64)
     * @Assert\Length(min="1", max="64", minMessage="validate.test.variables.name.min", maxMessage="validate.test.variables.name.max")
     * @Assert\NotBlank(message="validate.test.variables.name.blank")
     * @Assert\Regex("/^\.?[a-zA-Z][a-zA-Z0-9_]*(?<!_)$/", message="validate.test.variables.name.incorrect")
     */
    private $name;

    /**
     *
     * @var string
     * @ORM\Column(type="text")
     */
    private $description;

    /**
     *
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    private $value;

    /**
     *
     * @var boolean
     * @ORM\Column(type="boolean")
     */
    private $passableThroughUrl;

    /**
     *
     * @var integer
     * @ORM\Column(type="integer")
     */
    private $type;

    /**
     * @ORM\ManyToOne(targetEntity="Test", inversedBy="variables")
     */
    private $test;

    /**
     * @ORM\JoinColumn(name="parentVariable_id", referencedColumnName="id", nullable=true)
     * @ORM\ManyToOne(targetEntity="TestVariable", inversedBy="childVariables")
     */
    private $parentVariable;

    /**
     * @ORM\OneToMany(targetEntity="TestVariable", mappedBy="parentVariable", cascade={"remove"}, orphanRemoval=true)
     */
    private $childVariables;

    /**
     * @ORM\OneToMany(targetEntity="TestWizardParam", mappedBy="variable", cascade={"remove"}, orphanRemoval=true)
     */
    private $params;

    /**
     * @ORM\OneToMany(targetEntity="TestNodePort", mappedBy="variable", cascade={"remove"}, orphanRemoval=true)
     */
    private $ports;

    public function __construct()
    {
        parent::__construct();

        $this->description = "";
        $this->childVariables = new ArrayCollection();
        $this->params = new ArrayCollection();
        $this->ports = new ArrayCollection();
    }

    public function __toString()
    {
        return "TestVariable (#" . $this->getId() . ", name:" . $this->getName() . ", test name: " . $this->getTest()->getName() . ")";
    }

    public function getOwner()
    {
        return $this->getTest()->getOwner();
    }

    /**
     * Set name
     *
     * @param string $name
     * @return TestVariable
     */
    public function setName($name)
    {
        $this->name = $name;

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
     * Set description
     *
     * @param string $description
     * @return TestVariable
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return TestVariable
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
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
     * Set whether parameter is passable through URL
     *
     * @param boolean $passableThroughUrl
     * @return TestVariable
     */
    public function setPassableThroughUrl($passableThroughUrl)
    {
        $this->passableThroughUrl = $passableThroughUrl;

        return $this;
    }

    /**
     * Check if parameter is passable through URL
     *
     * @return boolean
     */
    public function isPassableThroughUrl()
    {
        return $this->passableThroughUrl;
    }

    /**
     * Set type
     *
     * @param integer $type
     * @return TestVariable
     */
    public function setType($type)
    {
        $this->type = $type;

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
     * Set test
     *
     * @param Test $test
     * @return TestVariable
     */
    public function setTest(Test $test = null)
    {
        $this->test = $test;

        return $this;
    }

    /**
     * Get test
     *
     * @return Test
     */
    public function getTest()
    {
        return $this->test;
    }

    /**
     * Add test wizard params
     *
     * @param TestWizardParam $param
     * @return TestVariable
     */
    public function addParam(TestWizardParam $param)
    {
        $this->params[] = $param;

        return $this;
    }

    /**
     * Remove test wizard param
     *
     * @param TestWizardParam $param
     */
    public function removeParam(TestWizardParam $param)
    {
        $this->params->removeElement($param);
    }

    /**
     * Get params
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params->toArray();
    }

    public function hasParam(TestWizardParam $param)
    {
        return $this->params->contains($param);
    }

    public function getPorts()
    {
        return $this->ports->toArray();
    }

    public function hasPort(TestNodePort $port)
    {
        return $this->ports->contains($port);
    }

    public function addPort(TestNodePort $port)
    {
        $this->ports->add($port);
        return $this;
    }

    public function removePort(TestNodePort $port)
    {
        $this->ports->removeElement($port);
        return $this;
    }

    /**
     * Set parent variable
     *
     * @param TestVariable $parent
     * @return TestVariable
     */
    public function setParentVariable($parent)
    {
        $this->parentVariable = $parent;

        return $this;
    }

    /**
     * Get parent variable
     *
     * @return TestVariable
     */
    public function getParentVariable()
    {
        return $this->parentVariable;
    }

    /**
     * Add child variable
     *
     * @param TestVariable $child
     * @return TestVariable
     */
    public function addChildVariable(TestVariable $child)
    {
        $this->childVariables[] = $child;

        return $this;
    }

    /**
     * Remove child variable
     *
     * @param TestVariable $child
     */
    public function removeChildVariable(TestVariable $child)
    {
        $this->childVariables->removeElement($child);
    }

    /**
     * Get child variables
     *
     * @return array
     */
    public function getChildVariables()
    {
        return $this->childVariables->toArray();
    }

    public function hasChildVariable(TestVariable $child)
    {
        return $this->childVariables->contains($child);
    }

    public function getAccessibility()
    {
        return $this->getTest()->getAccessibility();
    }

    public function hasDefaultValueSet()
    {
        return $this->value !== null && $this->value !== "";
    }

    public function hasAnyFromGroup($other_groups)
    {
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

    public function getLockBy()
    {
        return $this->getTest()->getLockBy();
    }

    public function getTopEntity()
    {
        return $this->getTest();
    }

    public function getEntityHash()
    {
        $json = json_encode(array(
            "name" => $this->getName(),
            "type" => $this->getType(),
            "description" => $this->getDescription(),
            "passableThroughUrl" => $this->isPassableThroughUrl(),
            "value" => $this->getValue()
        ));
        return sha1($json);
    }

    public function jsonSerialize(&$dependencies = array(), &$normalizedIdsMap = null)
    {
        $wizard = $this->getTest()->getSourceWizard();
        if ($wizard) {
            foreach ($wizard->getParams() as $param) {
                if ($this->getParentVariable() == null)
                    continue;
                if ($param->getVariable()->getId() == $this->getParentVariable()->getId()) {
                    TestWizardParam::getParamValueDependencies($this->value, $param->getDefinition(), $param->getType(), $dependencies);
                    break;
                }
            }
        }

        $serialized = array(
            "class_name" => "TestVariable",
            "id" => $this->getId(),
            "name" => $this->getName(),
            "type" => $this->getType(),
            "description" => $this->getDescription(),
            "passableThroughUrl" => $this->isPassableThroughUrl() ? "1" : "0",
            "value" => $this->getValue(),
            "test" => $this->getTest()->getId(),
            "parentVariable" => $this->getParentVariable() ? $this->getParentVariable()->getId() : null
        );

        if ($normalizedIdsMap !== null) {
            $serialized["id"] = self::normalizeId("TestVariable", $serialized["id"], $normalizedIdsMap);
            $serialized["test"] = self::normalizeId("Test", $serialized["test"], $normalizedIdsMap);
            $serialized["parentVariable"] = self::normalizeId("TestVariable", $serialized["parentVariable"], $normalizedIdsMap);
        }

        return $serialized;
    }

    /** @ORM\PreRemove */
    public function preRemove()
    {
        if ($this->getParentVariable()) $this->getParentVariable()->removeChildVariable($this);
        $this->getTest()->removeVariable($this);
    }

    /** @ORM\PrePersist */
    public function prePersist()
    {
        if ($this->getParentVariable() && !$this->getParentVariable()->hasChildVariable($this)) $this->getParentVariable()->addChildVariable($this);
        if (!$this->getTest()->hasVariable($this)) $this->getTest()->addVariable($this);
    }
}
