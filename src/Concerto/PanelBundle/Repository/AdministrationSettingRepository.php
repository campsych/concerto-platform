<?php

namespace Concerto\PanelBundle\Repository;

/**
 * AdministrationSettingRepository
 */
class AdministrationSettingRepository extends AEntityRepository {

    public function findKey($key) {
        return $this->findOneBy(array("skey" => $key));
    }

    public function findAllExposed() {
        return $this->findBy(array("exposed" => true));
    }

    public function findAllInternal() {
        return $this->findBy(array("exposed" => false));
    }

}
