<?php

namespace Concerto\PanelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table
 * @ORM\Entity(repositoryClass="Concerto\PanelBundle\Repository\AdministrationSettingRepository")
 */
class AdministrationSetting implements \JsonSerializable {
    
    /**
     * @var integer
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

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
     *
     * @var boolean
     * @ORM\Column(type="boolean")
     */
    private $exposed;
    
    /**
     * Get id
     *
     * @return integer 
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set id
     * @param integer $id
     * @return AdministrationSetting;
     */
    public function setId($id) {
        $this->id = $id;
        return $this;
    }

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

    /**
     * Set exposed
     *
     * @param boolean $exposed
     * @return AdministrationSetting
     */
    public function setExposed($exposed) {
        $this->exposed = $exposed;

        return $this;
    }

    /**
     * Is exposed?
     *
     * @return boolean 
     */
    public function isExposed() {
        return $this->exposed;
    }

    public function jsonSerialize(&$dependencies = array()) {
        return array(
            "class_name" => "AdministrationSetting",
            "id" => $this->getId(),
            "skey" => $this->skey,
            "svalue" => $this->svalue,
            "exposed" => $this->exposed
        );
    }

}
