<?php

namespace Concerto\PanelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table
 * @ORM\Entity(repositoryClass="Concerto\PanelBundle\Repository\AdministrationSettingRepository")
 */
class AdministrationSetting extends AEntity implements \JsonSerializable {

    /**
     *
     * @var string
     * @ORM\Column(type="string")
     */
    private $skey;

    /**
     *
     * @var string
     * @ORM\Column(type="string")
     */
    private $svalue;

    /**
     * Set key
     *
     * @param string $skey
     * @return AdministrationSetting
     */
    public function setKey($skey) {
        $this->skey = $skey;

        return $this;
    }

    /**
     * Get key
     *
     * @return string 
     */
    public function getKey() {
        return $this->skey;
    }

    /**
     * Set value
     *
     * @param string $svalue
     * @return AdministrationSetting
     */
    public function setValue($svalue) {
        $this->svalue = $svalue;

        return $this;
    }

    /**
     * Get value
     *
     * @return string 
     */
    public function getValue() {
        return $this->svalue;
    }

    public function getOwner() {
        return null;
    }

    public function jsonSerialize(&$dependencies = array()) {
        return array(
            "class_name" => "AdministrationSetting",
            "id" => $this->getId(),
            "created" => $this->getCreated()->format("Y-m-d H:i:s"),
            "skey" => $this->skey,
            "svalue" => $this->svalue
        );
    }

}
