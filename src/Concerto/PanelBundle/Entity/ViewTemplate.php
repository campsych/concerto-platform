<?php

namespace Concerto\PanelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Table
 * @ORM\Entity(repositoryClass="Concerto\PanelBundle\Repository\ViewTemplateRepository")
 * @UniqueEntity(fields="name", message="validate.table.name.unique")
 */
class ViewTemplate extends ATopEntity implements \JsonSerializable {

    /**
     * @var string
     * @Assert\Length(min="1", max="64", minMessage="validate.template.name.min", maxMessage="validate.template.name.max")
     * @Assert\NotBlank(message="validate.template.name.blank")
     * @ORM\Column(type="string", length=64, unique=true)
     */
    private $name;

    /**
     *
     * @var string
     * @ORM\Column(type="text")
     */
    private $description;

    /**
     *
     * @var string
     * @ORM\Column(type="text")
     */
    private $head;

    /**
     *
     * @var string
     * @ORM\Column(type="text")
     */
    private $css;

    /**
     *
     * @var string
     * @ORM\Column(type="text")
     */
    private $js;

    /**
     *
     * @var string
     * @ORM\Column(type="text")
     */
    private $html;
    
    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $owner;

    public function __construct() {
        parent::__construct();

        $this->description = "";
        $this->head = "";
        $this->css = "";
        $this->js = "";
        $this->html = "";
    }

    /**
     * Set name
     *
     * @param string $name
     * @return string
     */
    public function setName($name) {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return DataTable
     */
    public function setDescription($description) {
        $this->description = $description;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Set head
     *
     * @param string $head
     * @return ViewTemplate
     */
    public function setHead($head) {
        $this->head = $head;

        return $this;
    }

    /**
     * Get head
     *
     * @return string 
     */
    public function getHead() {
        return $this->head;
    }

    /**
     * Set css
     *
     * @param string $css
     * @return ViewTemplate
     */
    public function setCss($css) {
        $this->css = $css;

        return $this;
    }

    /**
     * Get css
     *
     * @return string 
     */
    public function getCss() {
        return $this->css;
    }

    /**
     * Set js
     *
     * @param string $js
     * @return ViewTemplate
     */
    public function setJs($js) {
        $this->js = $js;

        return $this;
    }

    /**
     * Get js
     *
     * @return string 
     */
    public function getJs() {
        return $this->js;
    }

    /**
     * Set html
     *
     * @param string $html
     * @return ViewTemplate
     */
    public function setHtml($html) {
        $this->html = $html;

        return $this;
    }

    /**
     * Get html
     *
     * @return string 
     */
    public function getHtml() {
        return $this->html;
    }
    
    /**
     * Set owner
     * @param User $user
     */
    public function setOwner($user) {
        $this->owner = $user;

        return $this;
    }

    /**
     * Get owner
     *
     * @return User 
     */
    public function getOwner() {
        return $this->owner;
    }

    public static function getArrayHash($arr) {
        unset($arr["id"]);
        unset($arr["updatedOn"]);
        unset($arr["updatedBy"]);
        unset($arr["owner"]);
        $json = json_encode($arr);
        return sha1($json);
    }

    public function jsonSerialize(&$dependencies = array()) {
        if (self::isDependencyReserved($dependencies, "ViewTemplate", $this->id))
            return null;
        self::reserveDependency($dependencies, "ViewTemplate", $this->id);

        $serialized = array(
            "class_name" => "ViewTemplate",
            "id" => $this->id,
            "name" => $this->name,
            "description" => $this->description,
            "accessibility" => $this->accessibility,
            "archived" => $this->archived ? "1" : "0",
            "head" => $this->head,
            "css" => $this->css,
            "js" => $this->js,
            "html" => $this->html,
            "updatedOn" => $this->updated->format("Y-m-d H:i:s"),
            "updatedBy" => $this->updatedBy,
            "owner" => $this->getOwner() ? $this->getOwner()->getId() : null,
            "groups" => $this->groups,
            "starterContent" => $this->starterContent
        );

        self::addDependency($dependencies, $serialized);
        return $serialized;
    }

}
