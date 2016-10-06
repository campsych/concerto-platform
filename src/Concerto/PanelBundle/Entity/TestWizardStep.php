<?php

namespace Concerto\PanelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Concerto\PanelBundle\Entity\TestWizard;
use Concerto\PanelBundle\Entity\TestWizardParam;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Table
 * @ORM\Entity(repositoryClass="Concerto\PanelBundle\Repository\TestWizardStepRepository")
 */
class TestWizardStep extends AEntity implements \JsonSerializable {

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
    public function __construct() {
        parent::__construct();

        $this->description = "";
        $this->colsNum = 0;
        $this->params = new ArrayCollection();
    }

    public function getOwner() {
        return $this->getWizard()->getOwner();
    }

    /**
     * Set title
     *
     * @param string $title
     * @return TestWizardStep
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

    /**
     * Set description
     *
     * @param string $description
     * @return TestWizardStep
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
     * Get order number
     *
     * @return integer 
     */
    public function getOrderNum() {
        return $this->orderNum;
    }

    /**
     * Set order number
     *
     * @param string $orderNum
     * @return TestWizardStep
     */
    public function setOrderNum($orderNum) {
        $this->orderNum = $orderNum;

        return $this;
    }

    /**
     * Get columns number
     *
     * @return integer 
     */
    public function getColsNum() {
        return $this->colsNum;
    }

    /**
     * Set columns number
     *
     * @param string $colsNum
     * @return TestWizardStep
     */
    public function setColsNum($colsNum) {
        $this->colsNum = $colsNum;

        return $this;
    }

    /**
     * Get test wizard
     *
     * @return TestWizard 
     */
    public function getWizard() {
        return $this->wizard;
    }

    /**
     * Set test wizard
     *
     * @param TestWizard $wizard
     * @return TestWizardStep
     */
    public function setWizard($wizard) {
        $this->wizard = $wizard;

        return $this;
    }

    /**
     * Add test wizard params
     *
     * @param TestWizardParam $param
     * @return TestWizardStep
     */
    public function addParam(TestWizardParam $param) {
        $this->params[] = $param;

        return $this;
    }

    /**
     * Remove test wizard param
     *
     * @param TestWizardParam $param
     * @return TestWizardStep
     */
    public function removeParam(TestWizardParam $param) {
        $this->params->removeElement($param);
        return $this;
    }

    /**
     * Get params
     *
     * @return Collection 
     */
    public function getParams() {
        return $this->params;
    }

    public function getHash() {
        $arr = $this->jsonSerialize();
        unset($arr["id"]);
        unset($arr["wizard"]);
        $arr["params"] = array();
        foreach ($this->params->toArray() as $param) {
            array_push($arr["params"], $param->getHash());
        }

        $json = json_encode($arr);
        return sha1($json);
    }

    public function jsonSerialize() {
        return array(
            "class_name" => "TestWizardStep",
            "id" => $this->id,
            "title" => $this->title,
            "description" => $this->description,
            "orderNum" => $this->orderNum,
            "colsNum" => $this->colsNum,
            "wizard" => $this->wizard->getId(),
            "params" => $this->params->toArray()
        );
    }

}
