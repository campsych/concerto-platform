<?php

namespace Concerto\PanelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Table
 * @ORM\Entity(repositoryClass="Concerto\PanelBundle\Repository\DataTableRepository")
 * @UniqueEntity(fields="name", message="validate.table.name.unique")
 */
class DataTable extends ATopEntity implements \JsonSerializable {

    /**
     * @var string
     * @Assert\Length(min="1", max="64", minMessage="validate.table.name.min", maxMessage="validate.table.name.max")
     * @Assert\NotBlank(message="validate.table.name.blank")
     * @Assert\Regex("/^[a-zA-Z][a-zA-Z0-9_]*(?<!_)$/", message="validate.table.name.incorrect")
     * @ORM\Column(type="string", length=64, unique=true)
     */
    private $name;

    /**
     *
     * @var string
     * @ORM\Column(type="text")
     */
    private $description;
    private $columns;

    public function __construct() {
        parent::__construct();

        $this->description = "";
        $this->columns = array();
    }

    /**
     * Set name
     *
     * @param string $name
     * @return DataTable
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
     * Set columns
     *
     * @param array $columns
     * @return DataTable
     */
    public function setColumns($columns) {
        $this->columns = $columns;

        return $this;
    }

    /**
     * Get columns
     *
     * @return array 
     */
    public function getColumns() {
        return $this->columns;
    }

    public function jsonSerialize() {
        return array(
            "class_name" => "DataTable",
            "id" => $this->id,
            "name" => $this->name,
            "description" => $this->description,
            "accessibility" => $this->accessibility,
            "protected" => $this->protected ? "1" : "0",
            "archived" => $this->archived ? "1" : "0",
            "columns" => $this->columns,
            "updatedOn" => $this->updated->format("Y-m-d H:i:s"),
            "updatedByName" => $this->updatedBy != null ? $this->updatedBy->getUsername() : "",
            "owner" => $this->getOwner() ? $this->getOwner()->getId() : null,
            "groups" => $this->groups,
            "starterContent" => $this->starterContent
        );
    }

}
