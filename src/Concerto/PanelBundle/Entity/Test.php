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
 * @ORM\HasLifecycleCallbacks
 */
class Test extends ATopEntity implements \JsonSerializable
{

    const VISIBILITY_REGULAR = 0;
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
     * @ORM\OneToMany(targetEntity="TestSessionLog", mappedBy="test")
     */
    private $logs;

    /**
     * @ORM\OneToMany(targetEntity="TestVariable", mappedBy="test", cascade={"remove"}, orphanRemoval=true)
     */
    private $variables;

    /**
     * @ORM\OneToMany(targetEntity="TestWizard", mappedBy="test", cascade={"remove"}, orphanRemoval=true)
     */
    private $wizards;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    private $code;

    /**
     * @ORM\OneToMany(targetEntity="TestSession", mappedBy="test", cascade={"remove"}, orphanRemoval=true)
     */
    private $sessions;

    /**
     * @ORM\OneToMany(targetEntity="TestNode", mappedBy="flowTest", cascade={"remove"}, orphanRemoval=true)
     */
    private $nodes;

    /**
     * @ORM\OneToMany(targetEntity="TestNode", mappedBy="sourceTest", cascade={"remove"}, orphanRemoval=true)
     */
    private $sourceForNodes;

    /**
     * @ORM\OneToMany(targetEntity="TestNodeConnection", mappedBy="flowTest", cascade={"remove"}, orphanRemoval=true)
     */
    private $nodesConnections;

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
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    private $configOverride;

    /**
     * @var ViewTemplate
     * @ORM\ManyToOne(targetEntity="ViewTemplate", inversedBy="baseTemplateForTests")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $baseTemplate;

    /**
     *
     * @var boolean
     * @ORM\Column(type="boolean")
     */
    private $protected;

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
        $this->description = "";
        $this->slug = md5(mt_rand() . uniqid(true));
        $this->sourceWizard = null;
        $this->protected = false;
    }

    public function getDependantTests()
    {
        $result = [];
        foreach ($this->getWizards() as $wizard) {
            /** @var TestWizard $wizard */
            $results[] = $wizard->getResultingTests();
        }
        return $result;
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
     * Set config override
     *
     * @param string $config
     * @return Test
     */
    public function setConfigOverride($config)
    {
        $this->configOverride = $config;

        return $this;
    }

    /**
     * Get config override
     *
     * @return string
     */
    public function getConfigOverride()
    {
        return $this->configOverride;
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
     * @param TestVariable $variable
     * @return Test
     */
    public function addVariable(TestVariable $variable)
    {
        $this->variables->add($variable);

        return $this;
    }

    /**
     * Remove variables
     *
     * @param TestVariable $variable
     */
    public function removeVariable(TestVariable $variable)
    {
        $this->variables->removeElement($variable);
    }

    /**
     * Get variables
     *
     * @return array
     */
    public function getVariables()
    {
        return $this->variables->toArray();
    }

    public function hasVariable(TestVariable $variable)
    {
        return $this->variables->contains($variable);
    }

    public function getVariablesByType($type)
    {
        $vars = $this->variables->filter(function (TestVariable $variable) use ($type) {
            return $variable->getType() == $type;
        })->toArray();
        return array_values($vars);
    }

    /**
     * Get test wizards
     *
     * @return array
     */
    public function getWizards()
    {
        return $this->wizards->toArray();
    }

    public function hasWizard(TestWizard $wizard)
    {
        return $this->wizards->contains($wizard);
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
     * @return array
     */
    public function getNodes()
    {
        return $this->nodes->toArray();
    }

    public function hasNode(TestNode $node)
    {
        return $this->nodes->contains($node);
    }

    /**
     * Add node that this test is source for
     *
     * @param TestNode $node
     * @return Test
     */
    public function addSourceForNodes(TestNode $node)
    {
        $this->sourceForNodes[] = $node;

        return $this;
    }

    /**
     * Remove node that this test is source for
     *
     * @param TestNode $node
     */
    public function removeSourceForNodes(TestNode $node)
    {
        $this->sourceForNodes->removeElement($node);
    }

    /**
     * Get nodes that this test is source for
     *
     * @return array
     */
    public function getSourceForNodes()
    {
        return $this->sourceForNodes->toArray();
    }

    public function isSourceForNodes(TestNode $node)
    {
        return $this->sourceForNodes->contains($node);
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

    public function clearNodesConnections()
    {
        $this->nodesConnections->clear();
    }

    /**
     * Get nodes connections
     *
     * @return array
     */
    public function getNodesConnections()
    {
        return $this->nodesConnections->toArray();
    }

    public function hasNodeConnection(TestNodeConnection $connection)
    {
        return $this->nodesConnections->contains($connection);
    }

    public function getNodesConnectionBySourcePortVariable(TestVariable $variable)
    {
        return $this->nodesConnections->filter(function (TestNodeConnection $connection) use ($variable) {
            return $connection->getSourcePort() && $connection->getSourcePort()->getVariable() === $variable;
        })->toArray();
    }

    public function getNodesConnectionsBySourcePort(TestNodePort $sourcePort)
    {
        return $this->nodesConnections->filter(function (TestNodeConnection $connection) use ($sourcePort) {
            return $connection->getSourcePort() === $sourcePort;
        })->toArray();
    }

    public function getNodesConnectionsByDestinationPort(TestNodePort $destinationPort)
    {
        return $this->nodesConnections->filter(function (TestNodeConnection $connection) use ($destinationPort) {
            return $connection->getDestinationPort() === $destinationPort;
        })->toArray();
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
     * Set base template
     *
     * @param ViewTemplate $template
     * @return Test
     */
    public function setBaseTemplate($template)
    {
        $this->baseTemplate = $template;

        return $this;
    }

    /**
     * Get base template
     *
     * @return ViewTemplate
     */
    public function getBaseTemplate()
    {
        return $this->baseTemplate;
    }

    /**
     * @return boolean
     */
    public function isProtected(): bool
    {
        return $this->protected;
    }

    /**
     * @param boolean $protected
     * @return Test
     */
    public function setProtected(bool $protected): Test
    {
        $this->protected = $protected;
        return $this;
    }

    /**
     * Set owner
     * @param User $user
     * @return Test
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

    public function getEntityHash()
    {
        $json = json_encode(array(
            "name" => $this->getName(),
            "description" => $this->getDescription(),
            "type" => $this->getType(),
            "code" => $this->getCode(),
            "protected" => $this->isProtected(),
            "variables" => AEntity::getEntityCollectionHash($this->getVariables()),
            "nodes" => AEntity::getEntityCollectionHash($this->getNodes()),
            "nodesConnections" => AEntity::getEntityCollectionHash($this->getNodesConnections())
        ));
        return sha1($json);
    }

    public function __toString()
    {
        return "Test (name:" . $this->getName() . ")";
    }

    public function jsonSerialize(&$dependencies = array(), &$normalizedIdsMap = null)
    {
        if (self::isDependencyReserved($dependencies, "Test", $this->id))
            return null;
        self::reserveDependency($dependencies, "Test", $this->id);

        if ($this->sourceWizard != null)
            $this->sourceWizard->jsonSerialize($dependencies, $normalizedIdsMap);

        if ($this->baseTemplate != null)
            $this->baseTemplate->jsonSerialize($dependencies, $normalizedIdsMap);

        //sorting for prettier diffs
        $variables = $this->getVariables();
        usort($variables, function ($a, $b) {
            $compareResult = strcmp($a->getName(), $b->getName());
            if ($compareResult !== 0) return $compareResult;
            return strcmp($a->getType(), $b->getType());
        });

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
            "description" => $this->description,
            "variables" => self::jsonSerializeArray($variables, $dependencies, $normalizedIdsMap),
            "sourceWizard" => $this->sourceWizard != null ? $this->sourceWizard->getId() : null,
            "sourceWizardName" => $this->sourceWizard != null ? $this->sourceWizard->getName() : null,
            "sourceWizardTest" => $this->sourceWizard != null ? $this->sourceWizard->getTest()->getId() : null,
            "sourceWizardTestName" => $this->sourceWizard != null ? $this->sourceWizard->getTest()->getName() : null,
            "steps" => self::jsonSerializeArray($this->sourceWizard ? $this->sourceWizard->getSteps() : [], $dependencies, $normalizedIdsMap),
            "updatedOn" => $this->getUpdated()->getTimestamp(),
            "updatedBy" => $this->getUpdatedBy(),
            "lockedBy" => $this->getLockBy() ? $this->getLockBy()->getId() : null,
            "directLockBy" => $this->getDirectLockBy() ? $this->getDirectLockBy()->getId() : null,
            "nodes" => self::jsonSerializeArray($this->getNodes(), $dependencies, $normalizedIdsMap),
            "nodesConnections" => self::jsonSerializeArray($this->getNodesConnections(), $dependencies, $normalizedIdsMap),
            "baseTemplate" => $this->baseTemplate != null ? $this->baseTemplate->getId() : null,
            "tags" => $this->tags,
            "owner" => $this->getOwner() ? $this->getOwner()->getId() : null,
            "groups" => $this->groups,
            "protected" => $this->isProtected() ? "1" : "0",
            "starterContent" => $this->starterContent
        );

        if ($normalizedIdsMap !== null) {
            $serialized["id"] = self::normalizeId("Test", $serialized["id"], $normalizedIdsMap);
            $serialized["sourceWizard"] = self::normalizeId("TestWizard", $serialized["sourceWizard"], $normalizedIdsMap);
            $serialized["baseTemplate"] = self::normalizeId("ViewTemplate", $serialized["baseTemplate"], $normalizedIdsMap);
        }

        self::addDependency($dependencies, $serialized);
        return $serialized;
    }

    /** @ORM\PreRemove */
    public function preRemove()
    {
        if ($this->getSourceWizard()) $this->getSourceWizard()->removeResultingTest($this);
        if ($this->getBaseTemplate()) $this->getBaseTemplate()->removeBaseTemplateForTest($this);
    }

    /** @ORM\PrePersist */
    public function prePersist()
    {
        if ($this->getSourceWizard() && !$this->getSourceWizard()->isResultingTest($this)) $this->getSourceWizard()->addResultingTest($this);
        if ($this->getBaseTemplate() && !$this->getBaseTemplate()->isBaseTemplateForTest($this)) $this->getBaseTemplate()->addBaseTemplateForTest($this);
    }
}
