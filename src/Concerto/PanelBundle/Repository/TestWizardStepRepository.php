<?php

namespace Concerto\PanelBundle\Repository;

/**
 * TestWizardStepRepository
 */
class TestWizardStepRepository extends AEntityRepository {

    public function deleteByTestWizard($wizard_id) {
        $this->delete($this->findBy(array("wizard" => $wizard_id)));
    }
}
