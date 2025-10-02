<?php

namespace Concerto\PanelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;

/**
 * @ORM\Table
 * @ORM\Entity(repositoryClass="Concerto\PanelBundle\Repository\FundamentalPerformanceRepository")
 * @ORM\HasLifecycleCallbacks
 */
class FundamentalPerformance
{
    const FUNDAMENTAL_LISTENING = 'listening';
    const FUNDAMENTAL_GRASPING = 'grasping';
    const FUNDAMENTAL_RETENTION = 'retention';
    const FUNDAMENTAL_APPLICATION = 'application';

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
    private $testSession;

    /**
     * @var string
     * @ORM\Column(type="string", length=20)
     */
    private $fundamentalType;

    /**
     * @var float
     * @ORM\Column(type="decimal", precision=5, scale=2)
     */
    private $score;

    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    private $totalQuestions;

    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    private $correctAnswers;

    /**
     * @var float
     * @ORM\Column(type="decimal", precision=8, scale=2, nullable=true)
     */
    private $averageResponseTime;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    private $weaknessPattern;

    public function __construct()
    {
        $this->created = new DateTime("now");
        $this->updated = new DateTime("now");
        $this->score = 0.0;
        $this->totalQuestions = 0;
        $this->correctAnswers = 0;
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
     * @return FundamentalPerformance
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
        return $this;
    }

    /**
     * Set test session
     *
     * @param TestSession $testSession
     * @return FundamentalPerformance
     */
    public function setTestSession($testSession)
    {
        $this->testSession = $testSession;

        return $this;
    }

    /**
     * Get test session
     *
     * @return TestSession
     */
    public function getTestSession()
    {
        return $this->testSession;
    }

    /**
     * Set fundamental type
     *
     * @param string $fundamentalType
     * @return FundamentalPerformance
     */
    public function setFundamentalType($fundamentalType)
    {
        $this->fundamentalType = $fundamentalType;

        return $this;
    }

    /**
     * Get fundamental type
     *
     * @return string
     */
    public function getFundamentalType()
    {
        return $this->fundamentalType;
    }

    /**
     * Set score
     *
     * @param float $score
     * @return FundamentalPerformance
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
     * Set total questions
     *
     * @param integer $totalQuestions
     * @return FundamentalPerformance
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
     * Set correct answers
     *
     * @param integer $correctAnswers
     * @return FundamentalPerformance
     */
    public function setCorrectAnswers($correctAnswers)
    {
        $this->correctAnswers = $correctAnswers;

        return $this;
    }

    /**
     * Get correct answers
     *
     * @return integer
     */
    public function getCorrectAnswers()
    {
        return $this->correctAnswers;
    }

    /**
     * Set average response time
     *
     * @param float $averageResponseTime
     * @return FundamentalPerformance
     */
    public function setAverageResponseTime($averageResponseTime)
    {
        $this->averageResponseTime = $averageResponseTime;

        return $this;
    }

    /**
     * Get average response time
     *
     * @return float
     */
    public function getAverageResponseTime()
    {
        return $this->averageResponseTime;
    }

    /**
     * Set weakness pattern
     *
     * @param string $weaknessPattern
     * @return FundamentalPerformance
     */
    public function setWeaknessPattern($weaknessPattern)
    {
        $this->weaknessPattern = $weaknessPattern;

        return $this;
    }

    /**
     * Get weakness pattern
     *
     * @return string
     */
    public function getWeaknessPattern()
    {
        return $this->weaknessPattern;
    }

    /**
     * Get all available fundamental types
     *
     * @return array
     */
    public static function getFundamentalTypes()
    {
        return [
            self::FUNDAMENTAL_LISTENING,
            self::FUNDAMENTAL_GRASPING,
            self::FUNDAMENTAL_RETENTION,
            self::FUNDAMENTAL_APPLICATION
        ];
    }

    /** @ORM\PreUpdate() */
    public function preUpdate()
    {
        $this->setUpdated(new DateTime("now"));
    }
}
