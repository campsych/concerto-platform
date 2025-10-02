<?php

namespace Concerto\PanelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;

/**
 * @ORM\Table
 * @ORM\Entity(repositoryClass="Concerto\PanelBundle\Repository\PracticeSessionRepository")
 * @ORM\HasLifecycleCallbacks
 */
class PracticeSession
{
    const STATUS_ACTIVE = 0;
    const STATUS_COMPLETED = 1;
    const STATUS_ABANDONED = 2;

    const TYPE_TARGETED = 'targeted';
    const TYPE_MIXED = 'mixed';
    const TYPE_DIFFICULTY = 'difficulty';

    /**
     * @var integer
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    protected $created;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    protected $updated;

    /**
     * @ORM\ManyToOne(targetEntity="TestSession")
     * @ORM\JoinColumn(nullable=false)
     */
    private $sourceTestSession;

    /**
     * @var string
     * @ORM\Column(type="string", length=20)
     */
    private $type;

    /**
     * @var string
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $targetFundamental;

    /**
     * @var string
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $difficulty;

    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    private $status;

    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    private $totalQuestions;

    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    private $completedQuestions;

    /**
     * @var float
     * @ORM\Column(type="decimal", precision=5, scale=2)
     */
    private $score;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    private $recommendations;

    public function __construct()
    {
        $this->created = new DateTime("now");
        $this->updated = new DateTime("now");
        $this->status = self::STATUS_ACTIVE;
        $this->totalQuestions = 0;
        $this->completedQuestions = 0;
        $this->score = 0.0;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get created
     *
     * @return DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Get updated
     *
     * @return DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set updated
     *
     * @param DateTime $updated
     * @return PracticeSession
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
        return $this;
    }

    /**
     * Set source test session
     *
     * @param TestSession $sourceTestSession
     * @return PracticeSession
     */
    public function setSourceTestSession($sourceTestSession)
    {
        $this->sourceTestSession = $sourceTestSession;

        return $this;
    }

    /**
     * Get source test session
     *
     * @return TestSession
     */
    public function getSourceTestSession()
    {
        return $this->sourceTestSession;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return PracticeSession
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set target fundamental
     *
     * @param string $targetFundamental
     * @return PracticeSession
     */
    public function setTargetFundamental($targetFundamental)
    {
        $this->targetFundamental = $targetFundamental;

        return $this;
    }

    /**
     * Get target fundamental
     *
     * @return string
     */
    public function getTargetFundamental()
    {
        return $this->targetFundamental;
    }

    /**
     * Set difficulty
     *
     * @param string $difficulty
     * @return PracticeSession
     */
    public function setDifficulty($difficulty)
    {
        $this->difficulty = $difficulty;

        return $this;
    }

    /**
     * Get difficulty
     *
     * @return string
     */
    public function getDifficulty()
    {
        return $this->difficulty;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return PracticeSession
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set total questions
     *
     * @param integer $totalQuestions
     * @return PracticeSession
     */
    public function setTotalQuestions($totalQuestions)
    {
        $this->totalQuestions = $totalQuestions;

        return $this;
    }

    /**
     * Get total questions
     *
     * @return integer
     */
    public function getTotalQuestions()
    {
        return $this->totalQuestions;
    }

    /**
     * Set completed questions
     *
     * @param integer $completedQuestions
     * @return PracticeSession
     */
    public function setCompletedQuestions($completedQuestions)
    {
        $this->completedQuestions = $completedQuestions;

        return $this;
    }

    /**
     * Get completed questions
     *
     * @return integer
     */
    public function getCompletedQuestions()
    {
        return $this->completedQuestions;
    }

    /**
     * Set score
     *
     * @param float $score
     * @return PracticeSession
     */
    public function setScore($score)
    {
        $this->score = $score;

        return $this;
    }

    /**
     * Get score
     *
     * @return float
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * Set recommendations
     *
     * @param string $recommendations
     * @return PracticeSession
     */
    public function setRecommendations($recommendations)
    {
        $this->recommendations = $recommendations;

        return $this;
    }

    /**
     * Get recommendations
     *
     * @return string
     */
    public function getRecommendations()
    {
        return $this->recommendations;
    }

    /**
     * Get completion percentage
     *
     * @return float
     */
    public function getCompletionPercentage()
    {
        if ($this->totalQuestions == 0) {
            return 0.0;
        }
        return ($this->completedQuestions / $this->totalQuestions) * 100;
    }

    /** @ORM\PreUpdate() */
    public function preUpdate()
    {
        $this->setUpdated(new DateTime("now"));
    }
}
