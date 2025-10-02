<?php

namespace Concerto\PanelBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Concerto\PanelBundle\Entity\TestSession;

class FundamentalPerformanceRepository extends EntityRepository
{
    /**
     * Get performance data for a specific test session
     *
     * @param TestSession $testSession
     * @return array
     */
    public function findByTestSession(TestSession $testSession)
    {
        return $this->createQueryBuilder('fp')
            ->where('fp.testSession = :testSession')
            ->setParameter('testSession', $testSession)
            ->orderBy('fp.fundamentalType', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get performance data for a specific fundamental type across all sessions
     *
     * @param string $fundamentalType
     * @param int $limit
     * @return array
     */
    public function findByFundamentalType($fundamentalType, $limit = 100)
    {
        return $this->createQueryBuilder('fp')
            ->where('fp.fundamentalType = :fundamentalType')
            ->setParameter('fundamentalType', $fundamentalType)
            ->orderBy('fp.created', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get average performance by fundamental type
     *
     * @return array
     */
    public function getAveragePerformanceByFundamental()
    {
        $qb = $this->createQueryBuilder('fp')
            ->select('fp.fundamentalType, AVG(fp.score) as avgScore, COUNT(fp.id) as totalSessions')
            ->groupBy('fp.fundamentalType')
            ->orderBy('fp.fundamentalType', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Get performance trends for a specific fundamental type
     *
     * @param string $fundamentalType
     * @param int $days
     * @return array
     */
    public function getPerformanceTrends($fundamentalType, $days = 30)
    {
        $dateFrom = new \DateTime("-{$days} days");

        return $this->createQueryBuilder('fp')
            ->select('DATE(fp.created) as date, AVG(fp.score) as avgScore, COUNT(fp.id) as sessionCount')
            ->where('fp.fundamentalType = :fundamentalType')
            ->andWhere('fp.created >= :dateFrom')
            ->setParameter('fundamentalType', $fundamentalType)
            ->setParameter('dateFrom', $dateFrom)
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get weakness patterns for a specific fundamental type
     *
     * @param string $fundamentalType
     * @return array
     */
    public function getWeaknessPatterns($fundamentalType)
    {
        return $this->createQueryBuilder('fp')
            ->select('fp.weaknessPattern, COUNT(fp.id) as frequency')
            ->where('fp.fundamentalType = :fundamentalType')
            ->andWhere('fp.weaknessPattern IS NOT NULL')
            ->setParameter('fundamentalType', $fundamentalType)
            ->groupBy('fp.weaknessPattern')
            ->orderBy('frequency', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
