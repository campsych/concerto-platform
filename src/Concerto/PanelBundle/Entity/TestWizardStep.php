<?php

namespace Concerto\PanelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Table
 * @ORM\Entity(repositoryClass="Concerto\PanelBundle\Repository\TestWizardStepRepository")
 * @UniqueEntity(fields={"wizard","title"}, message="validate.test.wizards.steps.unique")
 * @ORM\HasLifecycleCallbacks
 */
class TestWizardStep extends AEntity implements \JsonSerializable
{

    /**
     * @var string
     * @Assert\Length(min="1", max="64", minMessage="validate.test.wizards.steps.title.min", maxMessage="validate.test.wizards.steps.title.max")
     * @Assert\NotBlank(message="validate.test.wizards.steps.title.blank")
     * @ORM\Column(type="string", length=64)
     */
    private $title;

    /**
     *
     * @var string
     * @ORM\Column(type="text")
     */
    private $description;

    /**
     *
     * @var integer
     * @ORM\Column(type="integer")
     */
    private $orderNum;

    /**
     *
     * @var integer
     * @ORM\Column(type="integer")
     */
    private $colsNum;

    /**
     * @ORM\ManyToOne(targetEntity="TestWizard", inversedBy="steps")
     */
    private $wizard;

    /**
     * @ORM\OneToMany(targetEntity="TestWizardParam", mappedBy="step", cascade={"remove"}, orphanRemoval=true)
     */
    private $params;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->description = "";
        $this->colsNum = 0;
        $this->params = new ArrayCollection();
    }

    public function getOwner()
    {
        return $this->getWizard()->getOwner();
    }

    /**
     * Set title
     *
     * @param string $title
     * @return TestWizardStep
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

    /**
     * Set description
     *
     * @param string $description
     * @return TestWizardStep
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
     * Get order number
     *
     * @return integer
     */
    public function getOrderNum()
    {
        return $this->orderNum;
    }

    /**
     * Set order number
     *
     * @param string $orderNum
     * @return TestWizardStep
     */
    public function setOrderNum($orderNum)
    {
        $this->orderNum = $orderNum;

        return $this;
    }

    /**
     * Get columns number
     *
     * @return integer
     */
    public function getColsNum()
    {
        return $this->colsNum;
    }

    /**
     * Set columns number
     *
     * @param string $colsNum
     * @return TestWizardStep
     */
    public function setColsNum($colsNum)
    {
        $this->colsNum = $colsNum;

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
     * Set test wizard
     *
     * @param TestWizard $wizard
     * @return TestWizardStep
     */
    public function setWizard(TestWizard $wizard)
    {
        $this->wizard = $wizard;

        return $this;
    }

    /**
     * Get params
     *
     * @return array
     */
    public function getParams()
    {
        //return $this->getWizard()->getParamsByStep($this);
        return $this->params->toArray();
    }

    public function addParam(TestWizardParam $param)
    {
        $this->params->add($param);
        return $this;
    }

    public function removeParam(TestWizardParam $param)
    {
        $this->params->removeElement($param);
        return $this;
    }

    public function hasParam(TestWizardParam $param)
    {
        $this->params->contains($param);
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

    public function getEntityHash()
    {
        $json = json_encode(array(
            "title" => $this->getTitle(),
            "description" => $this->getDescription(),
            "orderNum" => $this->getOrderNum(),
            "params" => AEntity::getEntityCollectionHash($this->getParams())
        ));
        return sha1($json);
    }

    public function getTopEntity()
    {
        return $this->getWizard();
    }

    public function jsonSerialize(&$dependencies = array(), &$normalizedIdsMap = null)
    {
        //sorting for prettier diffs
        $params = $this->getParams();
        usort($params, function ($a, $b) {
            $compareResult = strcmp($a->getLabel(), $b->getLabel());
            return $compareResult;
        });

        $serialized = array(
            "class_name" => "TestWizardStep",
            "id" => $this->id,
            "title" => $this->title,
            "description" => $this->description,
            "orderNum" => $this->orderNum,
            "colsNum" => $this->colsNum,
            "wizard" => $this->wizard->getId(),
            "params" => self::jsonSerializeArray($params, $dependencies, $normalizedIdsMap)
        );

        if ($normalizedIdsMap !== null) {
            $serialized["id"] = self::normalizeId("TestWizardStep", $serialized["id"], $normalizedIdsMap);
            $serialized["wizard"] = self::normalizeId("TestWizard", $serialized["wizard"], $normalizedIdsMap);
        }

        return $serialized;
    }

    /** @ORM\PreRemove */
    public function preRemove()
    {
        $this->getWizard()->removeStep($this);
    }

    /** @ORM\PrePersist */
    public function prePersist()
    {
        if (!$this->getWizard()->hasStep($this)) $this->getWizard()->addStep($this);
    }
}
