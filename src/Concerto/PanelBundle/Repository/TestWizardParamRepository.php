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

    public function findByTestWizardAndType($wizard_id, $type)
    {
        $wizard = $this->getEntityManager()->getRepository("ConcertoPanelBundle:TestWizard")->find($wizard_id);
        if ($wizard) {
            return $wizard->getParamsByType($type);
        }
        return [];
    }

}
