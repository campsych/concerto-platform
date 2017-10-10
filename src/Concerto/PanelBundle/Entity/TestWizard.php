<?php

namespace Concerto\PanelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Table
 * @ORM\Entity(repositoryClass="Concerto\PanelBundle\Repository\TestWizardRepository")
 * @UniqueEntity(fields="name", message="validate.test.wizards.name.unique")
 */
class TestWizard extends ATopEntity implements \JsonSerializable {

    /**
     * @var string
     * @Assert\Length(min="1", max="64", minMessage="validate.test.wizards.name.min", maxMessage="validate.test.wizards.name.max")
     * @Assert\NotBlank(message="validate.test.wizards.name.blank")
     * @ORM\Column(type="string", length=64, unique=true)
     */
    private $name;

    /**
     *
     * @var string
     * @ORM\Column(type="text")
     */
    private $description;

    /**
     * @Assert\NotNull(message="validate.test.wizards.test.null")
     * @ORM\ManyToOne(targetEntity="Test", inversedBy="wizards")
     */
    private $test;

    /**
     * @ORM\OneToMany(targetEntity="TestWizardParam", mappedBy="wizard", cascade={"remove"})
     */
    private $params;

    /**
     * @ORM\OneToMany(targetEntity="TestWizardStep", mappedBy="wizard", cascade={"remove"})
     * @ORM\OrderBy({"orderNum" = "ASC"})
     */
    private $steps;

    /**
     * @ORM\OneToMany(targetEntity="Test", mappedBy="sourceWizard")
     */
    private $resultingTests;
    
    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $owner;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();

        $this->description = "";
        $this->params = new ArrayCollection();
        $this->steps = new ArrayCollection();
        $this->resultingTests = new ArrayCollection();
    }

    /**
     * Add resulting test
     *
     * @param Test $resultingTest
     * @return TestWizard
     */
    public function addResultingTest(Test $resultingTest) {
        $this->resultingTests[] = $resultingTest;

        return $this;
    }

    /**
     * Remove resulting test
     *
     * @param Test $resultingTest
     * @return TestWizard
     */
    public function removeResultingTest(Test $resultingTest) {
        $this->resultingTests->removeElement($resultingTest);
        return $this;
    }

    /**
     * Get resulting tests
     *
     * @return ArrayCollection 
     */
    public function getResultingTests() {
        return $this->resultingTests;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return TestWizard
     */
    public function setName($name) {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return TestWizard
     */
    public function setDescription($description) {
        $this->description = $description;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Set test
     *
     * @param Test $test
     * @return TestWizard
     */
    public function setTest($test) {
        $this->test = $test;

        return $this;
    }

    /**
     * Get test
     *
     * @return Test 
     */
    public function getTest() {
        return $this->test;
    }

    /**
     * Add test wizard params
     *
     * @param TestWizardParam $param
     * @return TestWizard
     */
    public function addParam(TestWizardParam $param) {
        $this->params[] = $param;
        return $this;
    }

    /**
     * Remove test wizard param
     *
     * @param TestWizardParam $param
     * @return TestWizard
     */
    public function removeParam(TestWizardParam $param) {
        $this->params->removeElement($param);
        return $this;
    }

    /**
     * Get params
     *
     * @return ArrayCollection 
     */
    public function getParams() {
        return $this->params;
    }

    public function getParamByName($name) {
        foreach ($this->params as $param) {
            if ($param->getVariable()->getName() == $name)
                return $param;
        }
        return null;
    }

    /**
     * Add test wizard step
     *
     * @param TestWizardStep $step
     * @return TestWizard
     */
    public function addStep(TestWizardStep $step) {
        $this->steps[] = $step;
        return $this;
    }

    /**
     * Remove test wizard step
     *
     * @param TestWizardStep $step
     * @return TestWizard
     */
    public function removeStep(TestWizardStep $step) {
        $this->steps->removeElement($step);
        return $this;
    }

    /**
     * Get steps
     *
     * @return ArrayCollection 
     */
    public function getSteps() {
        return $this->steps;
    }
    
    /**
     * Set owner
     * @param User $user
     */
    public function setOwner($user) {
        $this->owner = $user;

        return $this;
    }

    /**
     * Get owner
     *
     * @return User 
     */
    public function getOwner() {
        return $this->owner;
    }

    public static function getArrayHash($arr) {
        unset($arr["id"]);
        unset($arr["updatedOn"]);
        unset($arr["updatedBy"]);
        unset($arr["owner"]);
        for ($i = 0; $i < count($arr["steps"]); $i++) {
            $arr["steps"][$i] = TestWizardStep::getArrayHash($arr["steps"][$i]);
        }
        unset($arr["test"]);

        $json = json_encode($arr);
        return sha1($json);
    }

    public function jsonSerialize(&$dependencies = array()) {
        if (self::isDependencyReserved($dependencies, "TestWizard", $this->id))
            return null;
        self::reserveDependency($dependencies, "TestWizard", $this->id);

        $this->test->jsonSerialize($dependencies);

        $serialized = array(
            "class_name" => "TestWizard",
            "id" => $this->id,
            "name" => $this->name,
            "description" => $this->description,
            "accessibility" => $this->accessibility,
            "archived" => $this->archived ? "1" : "0",
            "steps" => self::jsonSerializeArray($this->steps->toArray(), $dependencies),
            "test" => $this->getTest()->getId(),
            "testName" => $this->getTest()->getName(),
            "updatedOn" => $this->updated->format("Y-m-d H:i:s"),
            "updatedBy" => $this->updatedBy,
            "owner" => $this->getOwner() ? $this->getOwner()->getId() : null,
            "groups" => $this->groups,
            "starterContent" => $this->starterContent
        );
        self::addDependency($dependencies, $serialized);
        return $serialized;
    }

}
