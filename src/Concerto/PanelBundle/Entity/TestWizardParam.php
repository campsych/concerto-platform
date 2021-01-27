<?php

namespace Concerto\PanelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Concerto\PanelBundle\Entity\TestWizardStep;
use Concerto\PanelBundle\Entity\TestWizard;
use Concerto\PanelBundle\Entity\TestVariable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Table
 * @ORM\Entity(repositoryClass="Concerto\PanelBundle\Repository\TestWizardParamRepository")
 * @UniqueEntity(fields={"wizard", "variable"}, message="validate.test.wizards.params.variable.unique")
 * @ORM\HasLifecycleCallbacks
 */
class TestWizardParam extends AEntity implements \JsonSerializable
{

    /**
     * @var string
     * @Assert\Length(min="1", max="64", minMessage="validate.test.wizards.params.label.min", maxMessage="validate.test.wizards.params.label.max")
     * @Assert\NotBlank(message="validate.test.wizards.params.label.blank")
     * @ORM\Column(type="string", length=64)
     */
    private $label;

    /**
     *
     * @var string
     * @ORM\Column(type="text")
     */
    private $description;

    /**
     *
     * @var string
     * @ORM\Column(type="text")
     */
    private $hideCondition;

    /**
     *
     * @var integer
     * @ORM\Column(type="integer")
     */
    private $type;

    /**
     *
     * @var boolean
     * @ORM\Column(type="boolean")
     */
    private $passableThroughUrl;

    /**
     * @Assert\NotNull(message="validate.test.wizards.params.step.null")
     * @ORM\ManyToOne(targetEntity="TestWizardStep", inversedBy="params")
     */
    private $step;

    /**
     *
     * @var integer
     * @ORM\Column(name="orderIndex", type="integer")
     */
    private $order;

    /**
     * @ORM\ManyToOne(targetEntity="TestWizard", inversedBy="params")
     */
    private $wizard;

    /**
     * @Assert\NotNull(message="validate.test.wizards.params.variable.null")
     * @ORM\ManyToOne(targetEntity="TestVariable", inversedBy="params")
     */
    private $variable;

    /**
     *
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    private $value;

    /**
     *
     * @var array
     * @ORM\Column(type="json")
     */
    private $definition;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->description = "";
        $this->hideCondition = "";
        $this->order = 0;
    }

    public function getOwner()
    {
        return $this->getWizard()->getOwner();
    }

    /**
     * Set label
     *
     * @param string $label
     * @return TestWizardParam
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return TestWizardParam
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
     * Set hide condition
     *
     * @param string $condition
     * @return TestWizardParam
     */
    public function setHideCondition($condition)
    {
        $this->hideCondition = $condition;

        return $this;
    }

    /**
     * Get hide condition
     *
     * @return string
     */
    public function getHideCondition()
    {
        return $this->hideCondition;
    }

    /**
     * Set type
     *
     * @param integer $type
     * @return TestWizardParam
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
     * Set wether parameter is passable through URL
     *
     * @param boolean $passableThroughUrl
     * @return TestWizardParam
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
     * Set test wizard
     *
     * @param TestWizard $wizard
     * @return TestWizardParam
     */
    public function setWizard(TestWizard $wizard)
    {
        $this->wizard = $wizard;

        return $this;
    }

    /**
     * Get test wizard
     *
     * @return TestWizard
     */
    public function getWizard()
    {
        return $this->wizard;
    }

    /**
     * Set order
     *
     * @param integer $order
     * @return TestWizardParam
     */
    public function setOrder($order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Get order
     *
     * @return integer
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set test variable
     *
     * @param TestVariable $variable
     * @return TestWizardParam
     */
    public function setVariable(TestVariable $variable)
    {
        $this->variable = $variable;

        return $this;
    }

    /**
     * Get test variable
     *
     * @return TestVariable
     */
    public function getVariable()
    {
        return $this->variable;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return TestWizardParam
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
     * Set test wizard step
     *
     * @param TestWizardStep $step
     * @return TestWizardParam
     */
    public function setStep(TestWizardStep $step)
    {
        $this->step = $step;

        return $this;
    }

    /**
     * Get test wizard step
     *
     * @return TestWizardStep
     */
    public function getStep()
    {
        return $this->step;
    }

    /**
     * Set definition
     *
     * @param array $def
     * @return TestWizardParam
     */
    public function setDefinition($def)
    {
        $this->definition = $def;

        return $this;
    }

    /**
     * Get definition
     *
     * @return array
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    public function getAccessibility()
    {
        return $this->getWizard()->getAccessibility();
    }

    public function hasAnyFromGroup($other_groups)
    {
        $groups = $this->getWizard()->getGroupsArray();
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
        return $this->getWizard()->getLockBy();
    }

    public static function getParamValueDependencies($val, $def, $type, &$dependencies = array())
    {
        if (!isset($dependencies["ids"]))
            $dependencies["ids"] = array();
        switch ($type) {
            case 5:
            {
                if ($val) {
                    if (!isset($dependencies["ids"]["ViewTemplate"]))
                        $dependencies["ids"]["ViewTemplate"] = array();
                    if (!in_array($val, $dependencies["ids"]["ViewTemplate"]))
                        array_push($dependencies["ids"]["ViewTemplate"], $val);
                }
                break;
            }
            case 6:
            {
                if ($val) {
                    if (!isset($dependencies["ids"]["DataTable"]))
                        $dependencies["ids"]["DataTable"] = array();
                    if (!in_array($val, $dependencies["ids"]["DataTable"]))
                        array_push($dependencies["ids"]["DataTable"], $val);
                }
                break;
            }
            case 7:
            case 12:
            {
                if (!is_array($val))
                    $val = json_decode($val, true);
                if (!$val)
                    return;
                if (isset($val["table"]) && $val["table"]) {
                    if (!isset($dependencies["ids"]["DataTable"]))
                        $dependencies["ids"]["DataTable"] = array();
                    if (!in_array($val["table"], $dependencies["ids"]["DataTable"]))
                        array_push($dependencies["ids"]["DataTable"], $val["table"]);
                }
                break;
            }
            case 8:
            {
                if ($val) {
                    if (!isset($dependencies["ids"]["Test"]))
                        $dependencies["ids"]["Test"] = array();
                    if (!in_array($val, $dependencies["ids"]["Test"]))
                        array_push($dependencies["ids"]["Test"], $val);
                }
                break;
            }
            case 9:
            {
                if (!is_array($val))
                    $val = json_decode($val, true);
                if (!$val)
                    return;
                foreach ($def["fields"] as $field) {
                    if (isset($val[$field["name"]]) && $val[$field["name"]]) {
                        $has_definition = isset($field["definition"]);
                        self::getParamValueDependencies($val[$field["name"]], $has_definition ? $field["definition"] : array(), $field["type"], $dependencies);
                    }
                }
                break;
            }
            case 10:
            {
                if (!is_array($val))
                    $val = json_decode($val, true);
                if (!is_array($val))
                    return;
                $has_definition = isset($def["element"]["definition"]);
                foreach ($val as $row) {
                    self::getParamValueDependencies($row, $has_definition ? $def["element"]["definition"] : array(), $def["element"]["type"], $dependencies);
                }
                break;
            }
            case 13:
            {
                if (is_array($def) && $def["test"]) {
                    if (!isset($dependencies["ids"]["Test"]))
                        $dependencies["ids"]["Test"] = array();
                    if (!in_array($def["test"], $dependencies["ids"]["Test"]))
                        array_push($dependencies["ids"]["Test"], $def["test"]);
                }
                break;
            }
        }
    }

    public function getEntityHash()
    {
        $json = json_encode(array(
            "label" => $this->getLabel(),
            "description" => $this->getDescription(),
            "hideCondition" => $this->getHideCondition(),
            "type" => $this->getType(),
            "passableThroughUrl" => $this->isPassableThroughUrl(),
            "value" => $this->getValue(),
            "name" => $this->getVariable()->getName(),
            "order" => $this->getOrder(),
            "definition" => $this->getDefinition()
        ));
        return sha1($json);
    }

    public function __toString()
    {
        return "TestWizardParam (#" . $this->getId() . ", label:" . $this->getLabel() . ")";
    }

    public function getTopEntity()
    {
        return $this->getWizard();
    }

    public function jsonSerialize(&$dependencies = array(), &$normalizedIdsMap = null)
    {
        self::getParamValueDependencies($this->value, $this->definition, $this->type, $dependencies);

        $serialized = array(
            "class_name" => "TestWizardParam",
            "id" => $this->id,
            "label" => $this->label,
            "description" => $this->description,
            "hideCondition" => $this->hideCondition,
            "type" => $this->type,
            "passableThroughUrl" => $this->isPassableThroughUrl() ? "1" : "0",
            "value" => $this->value,
            "testVariable" => $this->variable->getId(),
            "name" => $this->variable->getName(),
            "wizardStep" => $this->step->getId(),
            "stepTitle" => $this->step->getTitle(),
            "order" => $this->order,
            "wizard" => $this->wizard->getId(),
            "definition" => $this->definition
        );

        if ($normalizedIdsMap !== null) {
            $serialized["id"] = self::normalizeId("TestWizardParam", $serialized["id"], $normalizedIdsMap);
            $serialized["testVariable"] = self::normalizeId("TestVariable", $serialized["testVariable"], $normalizedIdsMap);
            $serialized["wizardStep"] = self::normalizeId("TestWizardStep", $serialized["wizardStep"], $normalizedIdsMap);
            $serialized["wizard"] = self::normalizeId("TestWizard", $serialized["wizard"], $normalizedIdsMap);
        }

        return $serialized;
    }

    /** @ORM\PreRemove */
    public function preRemove()
    {
        $this->getVariable()->removeParam($this);
        $this->getWizard()->removeParam($this);
        $this->getStep()->removeParam($this);
    }

    /** @ORM\PrePersist */
    public function prePersist()
    {
        if (!$this->getVariable()->hasParam($this)) $this->getVariable()->addParam($this);
        if (!$this->getWizard()->hasParam($this)) $this->getWizard()->addParam($this);
        if (!$this->getStep()->hasParam($this)) $this->getStep()->addParam($this);
    }
}
