<?php

namespace Concerto\PanelBundle\Repository;

use Concerto\PanelBundle\Entity\Test;

/**
 * TestRepository
 */
class TestRepository extends AEntityRepository
{

    public function findOneByName($name)
    {
        return $this->getEntityManager()->getRepository("ConcertoPanelBundle:Test")->findOneBy(array("name" => $name));
    }

    public function findByVisibility($visibility)
    {
        return $this->getEntityManager()->getRepository("ConcertoPanelBundle:Test")->findBy(array("visibility" => $visibility));
    }

    public function findOneBySlug($slug)
    {
        return $this->getEntityManager()->getRepository("ConcertoPanelBundle:Test")->findOneBy(array("slug" => $slug));
    }

    public function findRunnableBySlug($slug)
    {
        $qb = $this->getEntityManager()->createQueryBuilder()->select("t")->from("Concerto\PanelBundle\Entity\Test", "t")->where("t.slug = :slug")->andWhere("t.visibility != " . Test::VISIBILITY_SUBTEST)->setParameter("slug", $slug);
        $results = $qb->getQuery()->getResult();
        if(count($results) > 0) return $results[0];
        return null;
    }

    public function findRunnableByName($name)
    {
        $qb = $this->getEntityManager()->createQueryBuilder()->select("t")->from("Concerto\PanelBundle\Entity\Test", "t")->where("t.name = :name")->andWhere("t.visibility != " . Test::VISIBILITY_SUBTEST)->setParameter("name", $name);
        $results = $qb->getQuery()->getResult();
        if(count($results) > 0) return $results[0];
        return null;
    }

    public function findDependent($source_test_id)
    {
        $result = array();
        $wizards = $this->getEntityManager()->getRepository("ConcertoPanelBundle:TestWizard")->findBy(array("test" => $source_test_id));
        foreach ($wizards as $wiz) {
            $result = array_merge($result, $this->findBy(array("sourceWizard" => $wiz->getId())));
        }
        return $result;
    }

    public function markDependentTestsOutdated($source_test_id)
    {
        $wizards = $this->getEntityManager()->getRepository("ConcertoPanelBundle:TestWizard")->findBy(array("test" => $source_test_id));
        foreach ($wizards as $wiz) {
            $qb = $this->getEntityManager()->createQueryBuilder()->update("ConcertoPanelBundle:Test", "t")->set("t.outdated", 1)->where("t.sourceWizard = :id")->setParameter("id", $wiz->getId());
            $qb->getQuery()->getResult();
        }
    }

}
