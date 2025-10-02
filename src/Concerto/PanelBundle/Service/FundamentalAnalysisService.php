<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Entity\TestSession;
use Concerto\PanelBundle\Entity\TestSessionLog;
use Concerto\PanelBundle\Entity\TestNode;
use Concerto\PanelBundle\Entity\FundamentalPerformance;
use Concerto\PanelBundle\Entity\PracticeSession;
use Doctrine\ORM\EntityManagerInterface;

class FundamentalAnalysisService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Analyze test session and create fundamental performance records
     *
     * @param TestSession $testSession
     * @return array
     */
    public function analyzeTestSession(TestSession $testSession)
    {
        $test = $testSession->getTest();
        $nodes = $test->getNodes();
        
        $fundamentalData = [];
        $fundamentalTypes = FundamentalPerformance::getFundamentalTypes();
        
        // Initialize data for each fundamental type
        foreach ($fundamentalTypes as $type) {
            $fundamentalData[$type] = [
                'totalQuestions' => 0,
                'correctAnswers' => 0,
                'responseTimes' => [],
                'questions' => []
            ];
        }

        // Analyze each node (question) in the test
        foreach ($nodes as $node) {
            $fundamentalType = $node->getFundamentalType();
            if (!$fundamentalType) {
                continue; // Skip nodes without fundamental type
            }

            $fundamentalData[$fundamentalType]['totalQuestions']++;
            $fundamentalData[$fundamentalType]['questions'][] = $node;

            // Analyze session logs for this node
            $this->analyzeNodePerformance($node, $testSession, $fundamentalData[$fundamentalType]);
        }

        // Create FundamentalPerformance entities
        $performances = [];
        foreach ($fundamentalData as $type => $data) {
            if ($data['totalQuestions'] > 0) {
                $performance = $this->createFundamentalPerformance($testSession, $type, $data);
                $this->entityManager->persist($performance);
                $performances[] = $performance;
            }
        }

        $this->entityManager->flush();

        return $performances;
    }

    /**
     * Analyze performance for a specific node
     *
     * @param TestNode $node
     * @param TestSession $testSession
     * @param array $fundamentalData
     */
    private function analyzeNodePerformance(TestNode $node, TestSession $testSession, &$fundamentalData)
    {
        // This is a simplified analysis - in a real implementation,
        // you would analyze the actual response data from TestSessionLog
        // For now, we'll use mock data based on the node type and session parameters
        
        $sessionParams = json_decode($testSession->getParams(), true);
        
        // Mock analysis - in reality, you'd parse the actual response data
        $isCorrect = $this->mockCorrectnessAnalysis($node, $sessionParams);
        $responseTime = $this->mockResponseTimeAnalysis($node, $sessionParams);
        
        if ($isCorrect) {
            $fundamentalData['correctAnswers']++;
        }
        
        $fundamentalData['responseTimes'][] = $responseTime;
    }

    /**
     * Mock correctness analysis - replace with real analysis
     *
     * @param TestNode $node
     * @param array $sessionParams
     * @return bool
     */
    private function mockCorrectnessAnalysis(TestNode $node, $sessionParams)
    {
        // Mock logic - in reality, analyze actual responses
        $nodeId = $node->getId();
        return ($nodeId % 3) !== 0; // Mock 66% correctness rate
    }

    /**
     * Mock response time analysis - replace with real analysis
     *
     * @param TestNode $node
     * @param array $sessionParams
     * @return float
     */
    private function mockResponseTimeAnalysis(TestNode $node, $sessionParams)
    {
        // Mock logic - in reality, calculate from actual timestamps
        return rand(5, 30) + (rand(0, 100) / 100); // Random time between 5-30 seconds
    }

    /**
     * Create a FundamentalPerformance entity
     *
     * @param TestSession $testSession
     * @param string $fundamentalType
     * @param array $data
     * @return FundamentalPerformance
     */
    private function createFundamentalPerformance(TestSession $testSession, $fundamentalType, $data)
    {
        $performance = new FundamentalPerformance();
        $performance->setTestSession($testSession);
        $performance->setFundamentalType($fundamentalType);
        $performance->setTotalQuestions($data['totalQuestions']);
        $performance->setCorrectAnswers($data['correctAnswers']);
        
        $score = $data['totalQuestions'] > 0 ? ($data['correctAnswers'] / $data['totalQuestions']) * 100 : 0;
        $performance->setScore($score);
        
        if (!empty($data['responseTimes'])) {
            $avgResponseTime = array_sum($data['responseTimes']) / count($data['responseTimes']);
            $performance->setAverageResponseTime($avgResponseTime);
        }
        
        // Analyze weakness patterns
        $weaknessPattern = $this->analyzeWeaknessPattern($fundamentalType, $data);
        $performance->setWeaknessPattern($weaknessPattern);
        
        return $performance;
    }

    /**
     * Analyze weakness patterns for a fundamental type
     *
     * @param string $fundamentalType
     * @param array $data
     * @return string
     */
    private function analyzeWeaknessPattern($fundamentalType, $data)
    {
        $score = $data['totalQuestions'] > 0 ? ($data['correctAnswers'] / $data['totalQuestions']) * 100 : 0;
        $avgResponseTime = !empty($data['responseTimes']) ? 
            array_sum($data['responseTimes']) / count($data['responseTimes']) : 0;
        
        $patterns = [];
        
        if ($score < 60) {
            $patterns[] = 'low_accuracy';
        }
        
        if ($avgResponseTime > 20) {
            $patterns[] = 'slow_response';
        }
        
        if ($score < 40) {
            $patterns[] = 'severe_difficulty';
        }
        
        return implode(',', $patterns);
    }

    /**
     * Generate practice recommendations based on fundamental performance
     *
     * @param array $performances
     * @return array
     */
    public function generatePracticeRecommendations($performances)
    {
        $recommendations = [];
        
        foreach ($performances as $performance) {
            $type = $performance->getFundamentalType();
            $score = $performance->getScore();
            $weaknessPattern = $performance->getWeaknessPattern();
            
            if ($score < 70) {
                $recommendations[] = [
                    'fundamental' => $type,
                    'priority' => $score < 50 ? 'high' : 'medium',
                    'type' => 'targeted',
                    'reason' => $this->getRecommendationReason($type, $score, $weaknessPattern),
                    'suggestedQuestions' => $this->getSuggestedQuestionCount($score)
                ];
            }
        }
        
        // Sort by priority
        usort($recommendations, function($a, $b) {
            $priorityOrder = ['high' => 3, 'medium' => 2, 'low' => 1];
            return $priorityOrder[$b['priority']] - $priorityOrder[$a['priority']];
        });
        
        return $recommendations;
    }

    /**
     * Get recommendation reason
     *
     * @param string $fundamental
     * @param float $score
     * @param string $weaknessPattern
     * @return string
     */
    private function getRecommendationReason($fundamental, $score, $weaknessPattern)
    {
        $reasons = [
            'listening' => 'Focus on audio comprehension and following instructions',
            'grasping' => 'Practice understanding concepts and making connections',
            'retention' => 'Work on memory and recall techniques',
            'application' => 'Practice applying knowledge to new situations'
        ];
        
        $baseReason = $reasons[$fundamental] ?? 'Practice this fundamental skill';
        
        if (strpos($weaknessPattern, 'slow_response') !== false) {
            $baseReason .= ' with emphasis on speed';
        }
        
        if (strpos($weaknessPattern, 'severe_difficulty') !== false) {
            $baseReason .= ' - start with easier questions';
        }
        
        return $baseReason;
    }

    /**
     * Get suggested question count based on score
     *
     * @param float $score
     * @return int
     */
    private function getSuggestedQuestionCount($score)
    {
        if ($score < 40) {
            return 15; // More practice for very low scores
        } elseif ($score < 60) {
            return 10; // Moderate practice
        } else {
            return 5; // Light practice
        }
    }

    /**
     * Create a practice session based on recommendations
     *
     * @param TestSession $testSession
     * @param array $recommendation
     * @return PracticeSession
     */
    public function createPracticeSession(TestSession $testSession, $recommendation)
    {
        $practiceSession = new PracticeSession();
        $practiceSession->setSourceTestSession($testSession);
        $practiceSession->setType($recommendation['type']);
        $practiceSession->setTargetFundamental($recommendation['fundamental']);
        $practiceSession->setTotalQuestions($recommendation['suggestedQuestions']);
        $practiceSession->setRecommendations($recommendation['reason']);
        
        $this->entityManager->persist($practiceSession);
        $this->entityManager->flush();
        
        return $practiceSession;
    }
}
