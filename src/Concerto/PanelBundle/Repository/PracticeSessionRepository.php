<?php

namespace Concerto\PanelBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Concerto\PanelBundle\Entity\TestSession;

class PracticeSessionRepository extends EntityRepository
{
    /**
     * Get practice sessions for a specific test session
     *
     * @param TestSession $testSession
     * @return array
     */
    public function findByTestSession(TestSession $testSession)
    {
        return $this->createQueryBuilder('ps')
            ->where('ps.sourceTestSession = :testSession')
            ->setParameter('testSession', $testSession)
            ->orderBy('ps.created', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get active practice sessions
     *
     * @return array
     */
    public function findActiveSessions()
    {
        return $this->createQueryBuilder('ps')
            ->where('ps.status = :status')
            ->setParameter('status', \Concerto\PanelBundle\Entity\PracticeSession::STATUS_ACTIVE)
            ->orderBy('ps.created', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get practice sessions by type
     *
     * @param string $type
     * @return array
     */
    public function findByType($type)
    {
        return $this->createQueryBuilder('ps')
            ->where('ps.type = :type')
            ->setParameter('type', $type)
            ->orderBy('ps.created', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get practice sessions by target fundamental
     *
     * @param string $fundamental
     * @return array
     */
    public function findByTargetFundamental($fundamental)
    {
        return $this->createQueryBuilder('ps')
            ->where('ps.targetFundamental = :fundamental')
            ->setParameter('fundamental', $fundamental)
            ->orderBy('ps.created', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get average scores by practice type
     *
     * @return array
     */
    public function getAverageScoresByType()
    {
        return $this->createQueryBuilder('ps')
            ->select('ps.type, AVG(ps.score) as avgScore, COUNT(ps.id) as totalSessions')
            ->where('ps.status = :status')
            ->setParameter('status', \Concerto\PanelBundle\Entity\PracticeSession::STATUS_COMPLETED)
            ->groupBy('ps.type')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get practice effectiveness by fundamental
     *
     * @return array
     */
    public function getEffectivenessByFundamental()
    {
        return $this->createQueryBuilder('ps')
            ->select('ps.targetFundamental, AVG(ps.score) as avgScore, COUNT(ps.id) as totalSessions')
            ->where('ps.status = :status')
            ->andWhere('ps.targetFundamental IS NOT NULL')
            ->setParameter('status', \Concerto\PanelBundle\Entity\PracticeSession::STATUS_COMPLETED)
            ->groupBy('ps.targetFundamental')
            ->getQuery()
            ->getResult();
    }
}
