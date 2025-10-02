<?php

namespace Concerto\PanelBundle\Service;

use Concerto\PanelBundle\Entity\TestSession;
use Concerto\PanelBundle\Entity\TestSessionLog;
use Concerto\PanelBundle\Entity\TestNode;
use Doctrine\ORM\EntityManagerInterface;

class AIPredictionService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Predict fundamental weaknesses based on response patterns
     *
     * @param TestSession $testSession
     * @return array
     */
    public function predictFundamentalWeaknesses(TestSession $testSession)
    {
        $test = $testSession->getTest();
        $nodes = $test->getNodes();
        
        // Extract features from the test session
        $features = $this->extractFeatures($testSession, $nodes);
        
        // Apply ML model (simplified rule-based approach for MVP)
        $predictions = $this->applyPredictionModel($features);
        
        return $predictions;
    }

    /**
     * Extract features from test session for ML analysis
     *
     * @param TestSession $testSession
     * @param array $nodes
     * @return array
     */
    private function extractFeatures(TestSession $testSession, $nodes)
    {
        $features = [
            'response_times' => [],
            'accuracy_by_fundamental' => [],
            'session_duration' => 0,
            'total_questions' => count($nodes),
            'fundamental_distribution' => [],
            'difficulty_progression' => [],
            'time_patterns' => []
        ];

        $fundamentalTypes = ['listening', 'grasping', 'retention', 'application'];
        
        // Initialize fundamental tracking
        foreach ($fundamentalTypes as $type) {
            $features['accuracy_by_fundamental'][$type] = [
                'correct' => 0,
                'total' => 0,
                'response_times' => []
            ];
            $features['fundamental_distribution'][$type] = 0;
        }

        // Analyze each node
        foreach ($nodes as $node) {
            $fundamentalType = $node->getFundamentalType();
            if (!$fundamentalType) {
                continue;
            }

            $features['fundamental_distribution'][$fundamentalType]++;
            
            // Mock feature extraction - in reality, analyze actual response data
            $responseTime = $this->mockResponseTime($node);
            $isCorrect = $this->mockCorrectness($node);
            
            $features['response_times'][] = $responseTime;
            $features['accuracy_by_fundamental'][$fundamentalType]['total']++;
            $features['accuracy_by_fundamental'][$fundamentalType]['response_times'][] = $responseTime;
            
            if ($isCorrect) {
                $features['accuracy_by_fundamental'][$fundamentalType]['correct']++;
            }
        }

        // Calculate session duration (mock)
        $features['session_duration'] = $this->calculateSessionDuration($testSession);

        return $features;
    }

    /**
     * Apply prediction model to features
     *
     * @param array $features
     * @return array
     */
    private function applyPredictionModel($features)
    {
        $predictions = [];
        $fundamentalTypes = ['listening', 'grasping', 'retention', 'application'];

        foreach ($fundamentalTypes as $type) {
            $accuracy = $this->calculateAccuracy($features['accuracy_by_fundamental'][$type]);
            $avgResponseTime = $this->calculateAverageResponseTime($features['accuracy_by_fundamental'][$type]['response_times']);
            
            // Rule-based prediction logic
            $weaknessScore = $this->calculateWeaknessScore($accuracy, $avgResponseTime, $features);
            
            $predictions[$type] = [
                'weakness_score' => $weaknessScore,
                'confidence' => $this->calculateConfidence($features, $type),
                'predicted_accuracy' => $accuracy,
                'risk_factors' => $this->identifyRiskFactors($accuracy, $avgResponseTime, $features, $type)
            ];
        }

        return $predictions;
    }

    /**
     * Calculate accuracy for a fundamental type
     *
     * @param array $fundamentalData
     * @return float
     */
    private function calculateAccuracy($fundamentalData)
    {
        if ($fundamentalData['total'] == 0) {
            return 0.0;
        }
        
        return ($fundamentalData['correct'] / $fundamentalData['total']) * 100;
    }

    /**
     * Calculate average response time
     *
     * @param array $responseTimes
     * @return float
     */
    private function calculateAverageResponseTime($responseTimes)
    {
        if (empty($responseTimes)) {
            return 0.0;
        }
        
        return array_sum($responseTimes) / count($responseTimes);
    }

    /**
     * Calculate weakness score (0-100, higher = more likely to be weak)
     *
     * @param float $accuracy
     * @param float $avgResponseTime
     * @param array $features
     * @return float
     */
    private function calculateWeaknessScore($accuracy, $avgResponseTime, $features)
    {
        $score = 0;
        
        // Accuracy factor (40% weight)
        if ($accuracy < 60) {
            $score += 40;
        } elseif ($accuracy < 80) {
            $score += 20;
        }
        
        // Response time factor (30% weight)
        if ($avgResponseTime > 25) {
            $score += 30;
        } elseif ($avgResponseTime > 15) {
            $score += 15;
        }
        
        // Session duration factor (20% weight)
        if ($features['session_duration'] > 1800) { // 30 minutes
            $score += 20;
        } elseif ($features['session_duration'] > 1200) { // 20 minutes
            $score += 10;
        }
        
        // Total questions factor (10% weight)
        if ($features['total_questions'] < 5) {
            $score += 10; // Too few questions might indicate difficulty
        }
        
        return min(100, $score);
    }

    /**
     * Calculate confidence in prediction
     *
     * @param array $features
     * @param string $fundamentalType
     * @return float
     */
    private function calculateConfidence($features, $fundamentalType)
    {
        $confidence = 50; // Base confidence
        
        $questionCount = $features['fundamental_distribution'][$fundamentalType];
        
        // More questions = higher confidence
        if ($questionCount >= 5) {
            $confidence += 30;
        } elseif ($questionCount >= 3) {
            $confidence += 20;
        } elseif ($questionCount >= 1) {
            $confidence += 10;
        }
        
        // Longer session = higher confidence
        if ($features['session_duration'] > 600) { // 10 minutes
            $confidence += 20;
        }
        
        return min(100, $confidence);
    }

    /**
     * Identify risk factors for weakness
     *
     * @param float $accuracy
     * @param float $avgResponseTime
     * @param array $features
     * @param string $fundamentalType
     * @return array
     */
    private function identifyRiskFactors($accuracy, $avgResponseTime, $features, $fundamentalType)
    {
        $riskFactors = [];
        
        if ($accuracy < 50) {
            $riskFactors[] = 'low_accuracy';
        }
        
        if ($avgResponseTime > 20) {
            $riskFactors[] = 'slow_response';
        }
        
        if ($features['fundamental_distribution'][$fundamentalType] < 2) {
            $riskFactors[] = 'insufficient_data';
        }
        
        if ($features['session_duration'] > 1800) {
            $riskFactors[] = 'extended_session_time';
        }
        
        return $riskFactors;
    }

    /**
     * Mock response time calculation
     *
     * @param TestNode $node
     * @return float
     */
    private function mockResponseTime(TestNode $node)
    {
        // Mock logic - in reality, calculate from actual timestamps
        $baseTime = 10;
        $variation = rand(0, 20);
        return $baseTime + $variation;
    }

    /**
     * Mock correctness calculation
     *
     * @param TestNode $node
     * @return bool
     */
    private function mockCorrectness(TestNode $node)
    {
        // Mock logic - in reality, compare against correct answers
        return rand(1, 100) <= 70; // 70% correctness rate
    }

    /**
     * Calculate session duration
     *
     * @param TestSession $testSession
     * @return int
     */
    private function calculateSessionDuration(TestSession $testSession)
    {
        // Mock calculation - in reality, use created/updated timestamps
        return rand(300, 1800); // 5-30 minutes
    }

    /**
     * Get top predicted weaknesses
     *
     * @param array $predictions
     * @param int $limit
     * @return array
     */
    public function getTopWeaknesses($predictions, $limit = 2)
    {
        // Sort by weakness score
        uasort($predictions, function($a, $b) {
            return $b['weakness_score'] - $a['weakness_score'];
        });
        
        $topWeaknesses = [];
        $count = 0;
        
        foreach ($predictions as $fundamental => $data) {
            if ($count >= $limit) {
                break;
            }
            
            if ($data['weakness_score'] > 50) { // Only include significant weaknesses
                $topWeaknesses[] = [
                    'fundamental' => $fundamental,
                    'weakness_score' => $data['weakness_score'],
                    'confidence' => $data['confidence'],
                    'risk_factors' => $data['risk_factors']
                ];
                $count++;
            }
        }
        
        return $topWeaknesses;
    }
}
