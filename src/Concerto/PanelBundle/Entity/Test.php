<?php

namespace Concerto\PanelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Table
 * @ORM\Entity(repositoryClass="Concerto\PanelBundle\Repository\TestRepository")
 * @UniqueEntity(fields="name", message="validate.test.name.unique")
 */
class Test extends ATopEntity implements \JsonSerializable
{

    const VISIBILITY_REGULAR = 0;
    const VISIBILITY_FEATURED = 1;
    const VISIBILITY_SUBTEST = 2;
    const TYPE_CODE = 0;
    const TYPE_WIZARD = 1;
    const TYPE_FLOW = 2;

    /**
     * @var string
     * @Assert\Length(min="1", max="64", minMessage="validate.test.name.min", maxMessage="validate.test.name.max")
     * @Assert\NotBlank(message="validate.test.name.blank")
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
     *
     * @var integer
     * @ORM\Column(type="integer")
     */
    private $visibility;

    /**
     *
     * @var integer
     * @ORM\Column(type="integer")
     */
    private $type;

    /**
     * @ORM\OneToMany(targetEntity="TestSessionLog", mappedBy="test", cascade={"remove"})
     */
    private $logs;

    /**
     * @ORM\OneToMany(targetEntity="TestVariable", mappedBy="test", cascade={"remove"})
     */
    private $variables;

    /**
     * @ORM\OneToMany(targetEntity="TestWizard", mappedBy="test")
     */
    private $wizards;

    /**
     * @var string
     * @ORM\Column(type="text")
     */
    private $code;

    /**
     * @ORM\OneToMany(targetEntity="TestSession", mappedBy="test", cascade={"remove"})
     */
    private $sessions;

    /**
     * @ORM\OneToMany(targetEntity="TestNode", mappedBy="flowTest", cascade={"remove"})
     */
    private $nodes;

    /**
     * @ORM\OneToMany(targetEntity="TestNodeConnection", mappedBy="flowTest", cascade={"remove"})
     */
    private $nodesConnections;

    /**
     * @ORM\OneToMany(targetEntity="TestNode", mappedBy="sourceTest", cascade={"remove"})
     */
    private $sourceForNodes;

    /**
     *
     * @var boolean
     * @ORM\Column(type="boolean")
     */
    private $outdated;

    /**
     * @var TestWizard
     * @ORM\ManyToOne(targetEntity="TestWizard", inversedBy="resultingTests")
     */
    private $sourceWizard;

    /**
     *
     * @var string
     * @Assert\Length(min="1", max="64", minMessage="validate.test.slug.min", maxMessage="validate.test.slug.max")
     * @Assert\NotBlank(message="validate.test.slug.blank")
     * @ORM\Column(type="string", length=64, unique=true )
     */
    private $slug;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $owner;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->logs = new ArrayCollection();
        $this->variables = new ArrayCollection();
        $this->wizards = new ArrayCollection();
        $this->nodes = new ArrayCollection();
        $this->nodesConnections = new ArrayCollection();
        $this->sourceForNodes = new ArrayCollection();
        $this->code = "";
        $this->description = "";
        $this->outdated = false;
        $this->slug = md5(mt_rand() . uniqid(true));
        $this->sourceWizard = null;
    }

    /**
     * Get source test wizard
     *
     * @return TestWizard
     */
    public function getSourceWizard()
    {
        return $this->sourceWizard;
    }

    /**
     * Set source test wizard
     *
     * @param $sourceWizard
     * @return Test
     */
    public function setSourceWizard($sourceWizard)
    {
        $this->sourceWizard = $sourceWizard;

        return $this;
    }

    /**
     * Tells if test is based on test wizard
     *
     * @return boolean
     */
    public function isBasedOnWizard()
    {
        return $this->sourceWizard != null;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Test
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
     * @return Test
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Is newer version of source test available?
     *
     * @return boolean
     */
    public function isOutdated()
    {
        return $this->outdated;
    }

    /**
     * Set if newer version of source test is available.
     *
     * @param boolean $outdated
     */
    public function setOutdated($outdated)
    {
        $this->outdated = $outdated;
    }

    /**
     * Set code
     *
     * @param string $code
     * @return Test
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set visibility
     *
     * @param integer $visibility
     * @return Test
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Get visibility
     *
     * @return integer
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * Set type
     *
     * @param integer $type
     * @return Test
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
     * Add logs
     *
     * @param TestSessionLog $logs
     * @return Test
     */
    public function addLog(TestSessionLog $logs)
    {
        $this->logs[] = $logs;

        return $this;
    }

    /**
     * Remove logs
     *
     * @param TestSessionLog $logs
     */
    public function removeLog(TestSessionLog $logs)
    {
        $this->logs->removeElement($logs);
    }

    /**
     * Get logs
     *
     * @return ArrayCollection
     */
    public function getLogs()
    {
        return $this->logs;
    }

    /**
     * Add variables
     *
     * @param TestVariable $variables
     * @return Test
     */
    public function addVariable(TestVariable $variables)
    {
        $this->variables[] = $variables;

        return $this;
    }

    /**
     * Remove variables
     *
     * @param TestVariable $variables
     */
    public function removeVariable(TestVariable $variables)
    {
        $this->variables->removeElement($variables);
    }

    /**
     * Get variables
     *
     * @return ArrayCollection
     */
    public function getVariables()
    {
        return $this->variables;
    }

    /**
     * Get test wizards
     *
     * @return ArrayCollection
     */
    public function getWizards()
    {
        return $this->wizards;
    }

    /**
     * Add wizards
     *
     * @param TestWizard $wizards
     * @return Test
     */
    public function addWizard(TestWizard $wizards)
    {
        $this->wizards[] = $wizards;

        return $this;
    }

    /**
     * Remove wizards
     *
     * @param TestWizard $wizards
     */
    public function removeWizards(TestWizard $wizards)
    {
        $this->wizards->removeElement($wizards);
    }

    /**
     * Add sessions
     *
     * @param TestSession $sessions
     * @return Test
     */
    public function addSession(TestSession $sessions)
    {
        $this->sessions[] = $sessions;

        return $this;
    }

    /**
     * Remove sessions
     *
     * @param TestSession $sessions
     */
    public function removeSession(TestSession $sessions)
    {
        $this->sessions->removeElement($sessions);
    }

    /**
     * Get sessions
     *
     * @return ArrayCollection
     */
    public function getSessions()
    {
        return $this->sessions;
    }

    /**
     * Add node
     *
     * @param TestNode $node
     * @return Test
     */
    public function addNode(TestNode $node)
    {
        $this->nodes[] = $node;

        return $this;
    }

    /**
     * Remove node
     *
     * @param TestNode $node
     */
    public function removeNode(TestNode $node)
    {
        $this->nodes->removeElement($node);
    }

    public function clearNodes()
    {
        $this->nodes->clear();
    }

    /**
     * Get nodes
     *
     * @return ArrayCollection
     */
    public function getNodes()
    {
        return $this->nodes;
    }

    /**
     * Add node connection
     *
     * @param TestNodeConnection $connection
     * @return Test
     */
    public function addNodeConnection(TestNodeConnection $connection)
    {
        $this->nodesConnections[] = $connection;

        return $this;
    }

    /**
     * Remove node connection
     *
     * @param TestNodeConnection $connection
     */
    public function removeNodeConnection(TestNodeConnection $connection)
    {
        $this->nodesConnections->removeElement($connection);
    }

    /**
     * Get nodes connections
     *
     * @return ArrayCollection
     */
    public function getNodesConnections()
    {
        return $this->nodesConnections;
    }

    /**
     * Add source for node
     *
     * @param TestNode $node
     * @return Test
     */
    public function addSourceForNode(TestNode $node)
    {
        $this->sourceForNodes[] = $node;

        return $this;
    }

    /**
     * Remove source for node
     *
     * @param TestNode $node
     */
    public function removeSourceForNode(TestNode $node)
    {
        $this->sourceForNodes->removeElement($node);
    }

    /**
     * Get source for nodes
     *
     * @return ArrayCollection
     */
    public function getSourceForNodes()
    {
        return $this->sourceForNodes;
    }

    /**
     * Set url token
     *
     * @param string $slug
     * @return Test
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
        return $this;
    }

    /**
     * Get url token
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Checks if source test is selected for test wizard.
     *
     * @return boolean
     * @Assert\IsTrue(message = "validate.test.wizard.source")
     */
    public function hasWizardSource()
    {
        return $this->type != self::TYPE_WIZARD || ($this->type == self::TYPE_WIZARD && $this->sourceWizard != null);
    }

    /**
     * Set owner
     * @param User $user
     */
    public function setOwner($user)
    {
        $this->owner = $user;

        return $this;
    }

    /**
     * Get owner
     *
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    public static function getArrayHash($arr)
    {
        unset($arr["id"]);
        unset($arr["updatedOn"]);
        unset($arr["updatedBy"]);
        unset($arr["owner"]);
        for ($i = 0; $i < count($arr["variables"]); $i++) {
            $arr["variables"][$i] = TestVariable::getArrayHash($arr["variables"][$i]);
        }
        unset($arr["logs"]);
        unset($arr["slug"]);
        unset($arr["sourceWizard"]);
        unset($arr["sourceWizardTest"]);
        unset($arr["steps"]);
        for ($i = 0; $i < count($arr["nodes"]); $i++) {
            $arr["nodes"][$i] = TestNode::getArrayHash($arr["nodes"][$i]);
        }
        for ($i = 0; $i < count($arr["nodesConnections"]); $i++) {
            $arr["nodesConnections"][$i] = TestNodeConnection::getArrayHash($arr["nodesConnections"][$i]);
        }

        $json = json_encode($arr);
        return sha1($json);
    }

    public function __toString()
    {
        return "Test (name:" . $this->getName() . ")";
    }

    public function jsonSerialize(&$dependencies = array())
    {
        if (self::isDependencyReserved($dependencies, "Test", $this->id))
            return null;
        self::reserveDependency($dependencies, "Test", $this->id);

        if ($this->sourceWizard != null)
            $this->sourceWizard->jsonSerialize($dependencies);

        $serialized = array(
            "class_name" => "Test",
            "id" => $this->id,
            "name" => $this->name,
            "accessibility" => $this->accessibility,
            "archived" => $this->archived ? "1" : "0",
            "visibility" => $this->visibility,
            "type" => $this->type,
            "code" => $this->code,
            "slug" => $this->slug,
            "outdated" => $this->outdated ? "1" : "0",
            "description" => $this->description,
            "variables" => self::jsonSerializeArray($this->variables->toArray(), $dependencies),
            "logs" => $this->logs->toArray(),
            "sourceWizard" => $this->sourceWizard != null ? $this->sourceWizard->getId() : null,
            "sourceWizardName" => $this->sourceWizard != null ? $this->sourceWizard->getName() : null,
            "sourceWizardTest" => $this->sourceWizard != null ? $this->sourceWizard->getTest()->getId() : null,
            "sourceWizardTestName" => $this->sourceWizard != null ? $this->sourceWizard->getTest()->getName() : null,
            "steps" => self::jsonSerializeArray($this->sourceWizard ? $this->sourceWizard->getSteps()->toArray() : [], $dependencies),
            "updatedOn" => $this->updated->format("Y-m-d H:i:s"),
            "updatedBy" => $this->updatedBy,
            "nodes" => self::jsonSerializeArray($this->getNodes()->toArray(), $dependencies),
            "nodesConnections" => self::jsonSerializeArray($this->getNodesConnections()->toArray(), $dependencies),
            "tags" => $this->tags,
            "owner" => $this->getOwner() ? $this->getOwner()->getId() : null,
            "groups" => $this->groups,
            "starterContent" => $this->starterContent
        );

        self::addDependency($dependencies, $serialized);
        return $serialized;
    }

}
