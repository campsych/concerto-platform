<?php

namespace Concerto\PanelBundle\Repository;

/**
 * AdministrationSettingRepository
 */
class AdministrationSettingRepository extends AEntityRepository {

    public function findKey($key) {
        return $this->findOneBy(array("skey" => $key));
    }

}
