<?php

namespace Concerto\APIBundle\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Concerto\PanelBundle\Service\AdministrationService;
use Concerto\PanelBundle\Entity\PracticeSession;
use Concerto\PanelBundle\Entity\TestSession;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @Route("/api/practice")
 */
class PracticeSessionController
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
     * @Route("/session/{session_hash}/list", methods={"GET"})
     * @param Request $request
     * @param string $session_hash
     * @return Response
     */
    public function listPracticeSessionsAction(Request $request, $session_hash)
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

            $practiceSessions = $this->entityManager->getRepository(PracticeSession::class)
                ->findByTestSession($testSession);

            $result = [];
            foreach ($practiceSessions as $session) {
                $result[] = [
                    'id' => $session->getId(),
                    'type' => $session->getType(),
                    'target_fundamental' => $session->getTargetFundamental(),
                    'difficulty' => $session->getDifficulty(),
                    'status' => $this->getStatusText($session->getStatus()),
                    'total_questions' => $session->getTotalQuestions(),
                    'completed_questions' => $session->getCompletedQuestions(),
                    'completion_percentage' => $session->getCompletionPercentage(),
                    'score' => $session->getScore(),
                    'recommendations' => $session->getRecommendations(),
                    'created' => $session->getCreated()->format('Y-m-d H:i:s')
                ];
            }

            return new JsonResponse(['practice_sessions' => $result]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Route("/session/{practice_id}/start", methods={"POST"})
     * @param Request $request
     * @param int $practice_id
     * @return Response
     */
    public function startPracticeSessionAction(Request $request, $practice_id)
    {
        if (!$this->administrationService->isApiEnabled()) {
            return new Response("API disabled", Response::HTTP_FORBIDDEN);
        }

        try {
            $practiceSession = $this->entityManager->getRepository(PracticeSession::class)
                ->find($practice_id);

            if (!$practiceSession) {
                return new JsonResponse(['error' => 'Practice session not found'], Response::HTTP_NOT_FOUND);
            }

            if ($practiceSession->getStatus() !== PracticeSession::STATUS_ACTIVE) {
                return new JsonResponse(['error' => 'Practice session is not active'], Response::HTTP_BAD_REQUEST);
            }

            // Generate practice questions based on the session type and target
            $questions = $this->generatePracticeQuestions($practiceSession);

            return new JsonResponse([
                'practice_session_id' => $practiceSession->getId(),
                'type' => $practiceSession->getType(),
                'target_fundamental' => $practiceSession->getTargetFundamental(),
                'total_questions' => $practiceSession->getTotalQuestions(),
                'questions' => $questions
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Route("/session/{practice_id}/submit", methods={"POST"})
     * @param Request $request
     * @param int $practice_id
     * @return Response
     */
    public function submitPracticeSessionAction(Request $request, $practice_id)
    {
        if (!$this->administrationService->isApiEnabled()) {
            return new Response("API disabled", Response::HTTP_FORBIDDEN);
        }

        try {
            $content = json_decode($request->getContent(), true);
            $answers = $content['answers'] ?? [];

            $practiceSession = $this->entityManager->getRepository(PracticeSession::class)
                ->find($practice_id);

            if (!$practiceSession) {
                return new JsonResponse(['error' => 'Practice session not found'], Response::HTTP_NOT_FOUND);
            }

            // Calculate score based on answers
            $score = $this->calculatePracticeScore($answers, $practiceSession);
            $completedQuestions = count($answers);

            $practiceSession->setCompletedQuestions($completedQuestions);
            $practiceSession->setScore($score);
            $practiceSession->setStatus(PracticeSession::STATUS_COMPLETED);

            $this->entityManager->flush();

            return new JsonResponse([
                'practice_session_id' => $practiceSession->getId(),
                'score' => $score,
                'completed_questions' => $completedQuestions,
                'total_questions' => $practiceSession->getTotalQuestions(),
                'completion_percentage' => $practiceSession->getCompletionPercentage(),
                'status' => 'completed'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Route("/session/{practice_id}/progress", methods={"GET"})
     * @param Request $request
     * @param int $practice_id
     * @return Response
     */
    public function getPracticeProgressAction(Request $request, $practice_id)
    {
        if (!$this->administrationService->isApiEnabled()) {
            return new Response("API disabled", Response::HTTP_FORBIDDEN);
        }

        try {
            $practiceSession = $this->entityManager->getRepository(PracticeSession::class)
                ->find($practice_id);

            if (!$practiceSession) {
                return new JsonResponse(['error' => 'Practice session not found'], Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse([
                'practice_session_id' => $practiceSession->getId(),
                'status' => $this->getStatusText($practiceSession->getStatus()),
                'total_questions' => $practiceSession->getTotalQuestions(),
                'completed_questions' => $practiceSession->getCompletedQuestions(),
                'completion_percentage' => $practiceSession->getCompletionPercentage(),
                'current_score' => $practiceSession->getScore(),
                'target_fundamental' => $practiceSession->getTargetFundamental(),
                'recommendations' => $practiceSession->getRecommendations()
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Generate practice questions based on session parameters
     *
     * @param PracticeSession $practiceSession
     * @return array
     */
    private function generatePracticeQuestions(PracticeSession $practiceSession)
    {
        $questions = [];
        $totalQuestions = $practiceSession->getTotalQuestions();
        $targetFundamental = $practiceSession->getTargetFundamental();
        $type = $practiceSession->getType();

        // Mock question generation - in reality, you'd query the database
        // for actual questions based on the fundamental type and difficulty
        for ($i = 1; $i <= $totalQuestions; $i++) {
            $questions[] = [
                'id' => $i,
                'question' => $this->generateMockQuestion($targetFundamental, $i),
                'options' => $this->generateMockOptions($targetFundamental),
                'fundamental_type' => $targetFundamental,
                'difficulty' => $practiceSession->getDifficulty() ?? 'medium'
            ];
        }

        return $questions;
    }

    /**
     * Generate mock question based on fundamental type
     *
     * @param string $fundamental
     * @param int $questionNumber
     * @return string
     */
    private function generateMockQuestion($fundamental, $questionNumber)
    {
        $questions = [
            'listening' => [
                'Listen to the audio and answer: What is the main topic?',
                'Based on the conversation, what did the speaker emphasize?',
                'What instruction was given in the audio clip?'
            ],
            'grasping' => [
                'What is the relationship between these two concepts?',
                'How do these ideas connect to each other?',
                'What pattern do you notice in this sequence?'
            ],
            'retention' => [
                'What was mentioned earlier about this topic?',
                'Recall the key points from the previous section',
                'What information was provided in the introduction?'
            ],
            'application' => [
                'How would you apply this concept to a new situation?',
                'What would happen if you used this method in practice?',
                'How does this principle work in real-world scenarios?'
            ]
        ];

        $fundamentalQuestions = $questions[$fundamental] ?? ['What is the answer to this question?'];
        $index = ($questionNumber - 1) % count($fundamentalQuestions);
        
        return $fundamentalQuestions[$index];
    }

    /**
     * Generate mock answer options
     *
     * @param string $fundamental
     * @return array
     */
    private function generateMockOptions($fundamental)
    {
        return [
            'A) Option 1',
            'B) Option 2', 
            'C) Option 3',
            'D) Option 4'
        ];
    }

    /**
     * Calculate practice session score
     *
     * @param array $answers
     * @param PracticeSession $practiceSession
     * @return float
     */
    private function calculatePracticeScore($answers, $practiceSession)
    {
        if (empty($answers)) {
            return 0.0;
        }

        // Mock scoring - in reality, you'd compare against correct answers
        $correctCount = 0;
        foreach ($answers as $answer) {
            // Mock 70% correctness rate
            if (rand(1, 100) <= 70) {
                $correctCount++;
            }
        }

        return ($correctCount / count($answers)) * 100;
    }

    /**
     * Get status text from status code
     *
     * @param int $status
     * @return string
     */
    private function getStatusText($status)
    {
        $statusMap = [
            PracticeSession::STATUS_ACTIVE => 'active',
            PracticeSession::STATUS_COMPLETED => 'completed',
            PracticeSession::STATUS_ABANDONED => 'abandoned'
        ];

        return $statusMap[$status] ?? 'unknown';
    }
}
