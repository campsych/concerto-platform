<?php

namespace Concerto\APIBundle\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Concerto\PanelBundle\Service\FundamentalAnalysisService;
use Concerto\PanelBundle\Service\AdministrationService;
use Concerto\PanelBundle\Entity\TestSession;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @Route("/api/fundamental")
 */
class FundamentalAnalysisController
{
    private $analysisService;
    private $administrationService;
    private $entityManager;

    public function __construct(
        FundamentalAnalysisService $analysisService,
        AdministrationService $administrationService,
        EntityManagerInterface $entityManager
    ) {
        $this->analysisService = $analysisService;
        $this->administrationService = $administrationService;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/session/{session_hash}/analyze", methods={"POST"})
     * @param Request $request
     * @param string $session_hash
     * @return Response
     */
    public function analyzeSessionAction(Request $request, $session_hash)
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

            $performances = $this->analysisService->analyzeTestSession($testSession);
            $recommendations = $this->analysisService->generatePracticeRecommendations($performances);

            $result = [
                'session_id' => $testSession->getId(),
                'performances' => $this->serializePerformances($performances),
                'recommendations' => $recommendations
            ];

            return new JsonResponse($result);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Route("/session/{session_hash}/recommendations", methods={"GET"})
     * @param Request $request
     * @param string $session_hash
     * @return Response
     */
    public function getRecommendationsAction(Request $request, $session_hash)
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

            // Get existing performances for this session
            $performances = $this->entityManager->getRepository('Concerto\PanelBundle\Entity\FundamentalPerformance')
                ->findByTestSession($testSession);

            if (empty($performances)) {
                // If no performances exist, analyze the session first
                $performances = $this->analysisService->analyzeTestSession($testSession);
            }

            $recommendations = $this->analysisService->generatePracticeRecommendations($performances);

            return new JsonResponse([
                'session_id' => $testSession->getId(),
                'recommendations' => $recommendations
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Route("/session/{session_hash}/practice/create", methods={"POST"})
     * @param Request $request
     * @param string $session_hash
     * @return Response
     */
    public function createPracticeSessionAction(Request $request, $session_hash)
    {
        if (!$this->administrationService->isApiEnabled()) {
            return new Response("API disabled", Response::HTTP_FORBIDDEN);
        }

        try {
            $content = json_decode($request->getContent(), true);
            $fundamental = $content['fundamental'] ?? null;
            $type = $content['type'] ?? 'targeted';

            if (!$fundamental) {
                return new JsonResponse(['error' => 'Fundamental type is required'], Response::HTTP_BAD_REQUEST);
            }

            $testSession = $this->entityManager->getRepository(TestSession::class)
                ->findOneBy(['hash' => $session_hash]);

            if (!$testSession) {
                return new JsonResponse(['error' => 'Session not found'], Response::HTTP_NOT_FOUND);
            }

            $recommendation = [
                'fundamental' => $fundamental,
                'type' => $type,
                'priority' => 'medium',
                'reason' => 'Practice session for ' . $fundamental,
                'suggestedQuestions' => 10
            ];

            $practiceSession = $this->analysisService->createPracticeSession($testSession, $recommendation);

            return new JsonResponse([
                'practice_session_id' => $practiceSession->getId(),
                'type' => $practiceSession->getType(),
                'target_fundamental' => $practiceSession->getTargetFundamental(),
                'total_questions' => $practiceSession->getTotalQuestions(),
                'recommendations' => $practiceSession->getRecommendations()
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Route("/dashboard/student/{session_hash}", methods={"GET"})
     * @param Request $request
     * @param string $session_hash
     * @return Response
     */
    public function getStudentDashboardAction(Request $request, $session_hash)
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

            $performances = $this->entityManager->getRepository('Concerto\PanelBundle\Entity\FundamentalPerformance')
                ->findByTestSession($testSession);

            $dashboard = [
                'session_id' => $testSession->getId(),
                'test_name' => $testSession->getTest()->getName(),
                'overall_score' => $this->calculateOverallScore($performances),
                'fundamentals' => $this->serializePerformances($performances),
                'strengths' => $this->identifyStrengths($performances),
                'weaknesses' => $this->identifyWeaknesses($performances),
                'recommendations' => $this->analysisService->generatePracticeRecommendations($performances)
            ];

            return new JsonResponse($dashboard);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
