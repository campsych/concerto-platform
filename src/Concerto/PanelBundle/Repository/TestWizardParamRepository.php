<?php

namespace Concerto\PanelBundle\Repository;

/**
 * TestWizardParamRepository
 */
class TestWizardParamRepository extends AEntityRepository
{

    public function deleteByTestWizard($wizard_id)
    {
        $this->delete($this->findBy(array("wizard" => $wizard_id)));
    }

    public function findByTestWizardAndType($wizard, $type)
    {
        return $this->getEntityManager()->getRepository("ConcertoPanelBundle:TestWizardParam")->findBy(array("wizard" => $wizard, "type" => $type));
    }

}
