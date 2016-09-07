<?php

namespace Concerto\PanelBundle\Repository;

/**
 * TestRepository
 */
class TestRepository extends AEntityRepository {
    
    public function findOneByName($name) {
        return $this->getEntityManager()->getRepository("ConcertoPanelBundle:Test")->findOneBy(array("name" => $name));
    }

    public function findByVisibility($visibility) {
        return $this->getEntityManager()->getRepository("ConcertoPanelBundle:Test")->findBy(array("visibility" => $visibility));
    }

    public function findOneBySlug($slug) {
        return $this->getEntityManager()->getRepository("ConcertoPanelBundle:Test")->findOneBy(array("slug" => $slug));
    }

    public function findDependent($source_test_id) {
        $result = array();
        $wizards = $this->getEntityManager()->getRepository("ConcertoPanelBundle:TestWizard")->findBy(array("test" => $source_test_id));
        foreach ($wizards as $wiz) {
            $result = array_merge($result, $this->findBy(array("sourceWizard" => $wiz->getId())));
        }
        return $result;
    }

    public function markDependentTestsOutdated($source_test_id) {
        $wizards = $this->getEntityManager()->getRepository("ConcertoPanelBundle:TestWizard")->findBy(array("test" => $source_test_id));
        foreach ($wizards as $wiz) {
            $qb = $this->getEntityManager()->createQueryBuilder()->update("ConcertoPanelBundle:Test", "t")->set("t.outdated", 1)->where("t.sourceWizard = :id")->setParameter("id", $wiz->getId());
            $qb->getQuery()->getResult();
        }
    }

}
