<?php

namespace Concerto\APIBundle\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Concerto\PanelBundle\Service\AdministrationService;
use Concerto\PanelBundle\Entity\TestSession;
use Concerto\PanelBundle\Entity\FundamentalPerformance;
use Concerto\PanelBundle\Entity\PracticeSession;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @Route("/api/dashboard")
 */
class DashboardController
{
    private $administrationService;
    private $entityManager;

    public function __construct(
        AdministrationService $administrationService,
        EntityManagerInterface $entityManager
    ) {
        $this->administrationService = $administrationService;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/teacher/class/{class_id}", methods={"GET"})
     * @param Request $request
     * @param string $class_id
     * @return Response
     */
    public function getTeacherDashboardAction(Request $request, $class_id)
    {
        if (!$this->administrationService->isApiEnabled()) {
            return new Response("API disabled", Response::HTTP_FORBIDDEN);
        }

        try {
            // Get all test sessions for the class (mock implementation)
            $testSessions = $this->getClassTestSessions($class_id);
            
            $dashboard = [
                'class_id' => $class_id,
                'total_students' => count($testSessions),
                'fundamental_heatmap' => $this->generateFundamentalHeatmap($testSessions),
                'class_performance' => $this->calculateClassPerformance($testSessions),
                'weakness_summary' => $this->generateWeaknessSummary($testSessions),
                'recommendations' => $this->generateTeacherRecommendations($testSessions)
            ];

            return new JsonResponse($dashboard);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Route("/parent/student/{session_hash}", methods={"GET"})
     * @param Request $request
     * @param string $session_hash
     * @return Response
     */
    public function getParentDashboardAction(Request $request, $session_hash)
    {
        if (!$this->administrationService->isApiEnabled()) {
            return new Response("API disabled", Response::HTTP_FORBIDDEN);
        }

        try {
            $testSession = $this->entityManager->getRepository(TestSession::class)
                ->findOneBy(['hash' => $session_hash]);

            if (!$testSession) {
                return new JsonResponse(['error' => 'Session not found'], Response::HTTP_NOT_FOUND);
            }

            $performances = $this->entityManager->getRepository(FundamentalPerformance::class)
                ->findByTestSession($testSession);

            $dashboard = [
                'student_name' => 'Student Name', // Mock - would come from user data
                'test_name' => $testSession->getTest()->getName(),
                'overall_score' => $this->calculateOverallScore($performances),
                'strengths' => $this->identifyStrengths($performances),
                'areas_for_improvement' => $this->identifyWeaknesses($performances),
                'improvement_tips' => $this->generateParentTips($performances),
                'fundamental_breakdown' => $this->serializePerformances($performances)
            ];

            return new JsonResponse($dashboard);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Route("/analytics/fundamental-trends", methods={"GET"})
     * @param Request $request
     * @return Response
     */
    public function getFundamentalTrendsAction(Request $request)
    {
        if (!$this->administrationService->isApiEnabled()) {
            return new Response("API disabled", Response::HTTP_FORBIDDEN);
        }

        try {
            $fundamentalTypes = FundamentalPerformance::getFundamentalTypes();
            $trends = [];

            foreach ($fundamentalTypes as $type) {
                $trends[$type] = $this->entityManager->getRepository(FundamentalPerformance::class)
                    ->getPerformanceTrends($type, 30);
            }

            return new JsonResponse(['trends' => $trends]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get test sessions for a class (mock implementation)
     *
     * @param string $class_id
     * @return array
     */
    private function getClassTestSessions($class_id)
    {
        // Mock implementation - in reality, you'd query based on class_id
        return $this->entityManager->getRepository(TestSession::class)
            ->findBy([], null, 20); // Get last 20 sessions as mock
    }

    /**
     * Generate fundamental heatmap for class
     *
     * @param array $testSessions
     * @return array
     */
    private function generateFundamentalHeatmap($testSessions)
    {
        $heatmap = [];
        $fundamentalTypes = FundamentalPerformance::getFundamentalTypes();

        foreach ($fundamentalTypes as $type) {
            $heatmap[$type] = [
                'excellent' => 0,
                'good' => 0,
                'needs_improvement' => 0,
                'struggling' => 0
            ];
        }

        foreach ($testSessions as $session) {
            $performances = $this->entityManager->getRepository(FundamentalPerformance::class)
                ->findByTestSession($session);

            foreach ($performances as $performance) {
                $type = $performance->getFundamentalType();
                $score = $performance->getScore();

                if ($score >= 90) {
                    $heatmap[$type]['excellent']++;
                } elseif ($score >= 80) {
                    $heatmap[$type]['good']++;
                } elseif ($score >= 60) {
                    $heatmap[$type]['needs_improvement']++;
                } else {
                    $heatmap[$type]['struggling']++;
                }
            }
        }

        return $heatmap;
    }

    /**
     * Calculate class performance metrics
     *
     * @param array $testSessions
     * @return array
     */
    private function calculateClassPerformance($testSessions)
    {
        $totalSessions = count($testSessions);
        $totalScore = 0;
        $completedSessions = 0;

        foreach ($testSessions as $session) {
            $performances = $this->entityManager->getRepository(FundamentalPerformance::class)
                ->findByTestSession($session);

            if (!empty($performances)) {
                $sessionScore = $this->calculateOverallScore($performances);
                $totalScore += $sessionScore;
                $completedSessions++;
            }
        }

        return [
            'average_score' => $completedSessions > 0 ? $totalScore / $completedSessions : 0,
            'completion_rate' => $totalSessions > 0 ? ($completedSessions / $totalSessions) * 100 : 0,
            'total_sessions' => $totalSessions,
            'completed_sessions' => $completedSessions
        ];
    }

    /**
     * Generate weakness summary for class
     *
     * @param array $testSessions
     * @return array
     */
    private function generateWeaknessSummary($testSessions)
    {
        $weaknessCount = [];
        $fundamentalTypes = FundamentalPerformance::getFundamentalTypes();

        foreach ($fundamentalTypes as $type) {
            $weaknessCount[$type] = 0;
        }

        foreach ($testSessions as $session) {
            $performances = $this->entityManager->getRepository(FundamentalPerformance::class)
                ->findByTestSession($session);

            foreach ($performances as $performance) {
                if ($performance->getScore() < 60) {
                    $weaknessCount[$performance->getFundamentalType()]++;
                }
            }
        }

        return $weaknessCount;
    }

    /**
     * Generate teacher recommendations
     *
     * @param array $testSessions
     * @return array
     */
    private function generateTeacherRecommendations($testSessions)
    {
        $recommendations = [];
        $weaknessSummary = $this->generateWeaknessSummary($testSessions);

        foreach ($weaknessSummary as $fundamental => $count) {
            if ($count > 0) {
                $percentage = (count($testSessions) > 0) ? ($count / count($testSessions)) * 100 : 0;
                
                if ($percentage > 50) {
                    $recommendations[] = [
                        'fundamental' => $fundamental,
                        'priority' => 'high',
                        'message' => "Over 50% of students struggle with {$fundamental}. Consider additional instruction and practice materials."
                    ];
                } elseif ($percentage > 25) {
                    $recommendations[] = [
                        'fundamental' => $fundamental,
                        'priority' => 'medium',
                        'message' => "Some students need extra support with {$fundamental}. Provide targeted practice opportunities."
                    ];
                }
            }
        }

        return $recommendations;
    }

    /**
     * Generate parent tips based on performance
     *
     * @param array $performances
     * @return array
     */
    private function generateParentTips($performances)
    {
        $tips = [];
        $weaknesses = $this->identifyWeaknesses($performances);

        $tipMap = [
            'listening' => 'Encourage active listening during conversations and provide audio-based learning materials.',
            'grasping' => 'Help your child make connections between concepts by discussing real-world examples.',
            'retention' => 'Practice memory techniques like repetition and creating mental associations.',
            'application' => 'Encourage your child to apply what they learn in practical situations at home.'
        ];

        foreach ($weaknesses as $weakness) {
            if (isset($tipMap[$weakness])) {
                $tips[] = $tipMap[$weakness];
            }
        }

        // If no specific weaknesses, provide general tips
        if (empty($tips)) {
            $tips[] = 'Great job! Continue practicing to maintain these strong skills.';
        }

        return array_slice($tips, 0, 2); // Return max 2 tips
    }

    /**
     * Serialize performances for API response
     *
     * @param array $performances
     * @return array
     */
    private function serializePerformances($performances)
    {
        $result = [];
        foreach ($performances as $performance) {
            $result[] = [
                'fundamental' => $performance->getFundamentalType(),
                'score' => $performance->getScore(),
                'total_questions' => $performance->getTotalQuestions(),
                'correct_answers' => $performance->getCorrectAnswers(),
                'average_response_time' => $performance->getAverageResponseTime(),
                'weakness_pattern' => $performance->getWeaknessPattern()
            ];
        }
        return $result;
    }

    /**
     * Calculate overall score from performances
     *
     * @param array $performances
     * @return float
     */
    private function calculateOverallScore($performances)
    {
        if (empty($performances)) {
            return 0.0;
        }

        $totalScore = 0;
        foreach ($performances as $performance) {
            $totalScore += $performance->getScore();
        }

        return $totalScore / count($performances);
    }

    /**
     * Identify strengths from performances
     *
     * @param array $performances
     * @return array
     */
    private function identifyStrengths($performances)
    {
        $strengths = [];
        foreach ($performances as $performance) {
            if ($performance->getScore() >= 80) {
                $strengths[] = $performance->getFundamentalType();
            }
        }
        return $strengths;
    }

    /**
     * Identify weaknesses from performances
     *
     * @param array $performances
     * @return array
     */
    private function identifyWeaknesses($performances)
    {
        $weaknesses = [];
        foreach ($performances as $performance) {
            if ($performance->getScore() < 60) {
                $weaknesses[] = $performance->getFundamentalType();
            }
        }
        return $weaknesses;
    }
}
