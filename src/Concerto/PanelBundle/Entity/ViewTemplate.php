<?php

namespace Concerto\PanelBundle\Entity;

use Concerto\PanelBundle\Entity\Test;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Table
 * @ORM\Entity(repositoryClass="Concerto\PanelBundle\Repository\ViewTemplateRepository")
 * @UniqueEntity(fields="name", message="validate.table.name.unique")
 * @ORM\HasLifecycleCallbacks
 */
class ViewTemplate extends ATopEntity implements \JsonSerializable
{

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
     * @ORM\OneToMany(targetEntity="Test", mappedBy="baseTemplate")
     */
    private $baseTemplateForTests;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $owner;

    public function __construct()
    {
        parent::__construct();

        $this->description = "";
        $this->head = "";
        $this->css = "";
        $this->js = "";
        $this->html = "";
        $this->baseTemplateForTests = new ArrayCollection();
    }

    /**
     * Set name
     *
     * @param string $name
     * @return string
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return DataTable
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set head
     *
     * @param string $head
     * @return ViewTemplate
     */
    public function setHead($head)
    {
        $this->head = $head;

        return $this;
    }

    /**
     * Get head
     *
     * @return string
     */
    public function getHead()
    {
        return $this->head;
    }

    /**
     * Set css
     *
     * @param string $css
     * @return ViewTemplate
     */
    public function setCss($css)
    {
        $this->css = $css;

        return $this;
    }

    /**
     * Get css
     *
     * @return string
     */
    public function getCss()
    {
        return $this->css;
    }

    /**
     * Set js
     *
     * @param string $js
     * @return ViewTemplate
     */
    public function setJs($js)
    {
        $this->js = $js;

        return $this;
    }

    /**
     * Get js
     *
     * @return string
     */
    public function getJs()
    {
        return $this->js;
    }

    /**
     * Set html
     *
     * @param string $html
     * @return ViewTemplate
     */
    public function setHtml($html)
    {
        $this->html = $html;

        return $this;
    }

    /**
     * Get html
     *
     * @return string
     */
    public function getHtml()
    {
        return $this->html;
    }

    /**
     * Add test that uses template as base
     *
     * @param Test $test
     * @return ViewTemplate
     */
    public function addBaseTemplateForTest(Test $test)
    {
        $this->baseTemplateForTests[] = $test;

        return $this;
    }

    /**
     * Remove test that uses template as base
     *
     * @param Test $test
     */
    public function removeBaseTemplateForTest(Test $test)
    {
        $this->baseTemplateForTests->removeElement($test);
    }

    /**
     * Get tests that uses template as base
     *
     * @return array
     */
    public function getBaseTemplateForTests()
    {
        return $this->baseTemplateForTests->toArray();
    }

    public function isBaseTemplateForTest(Test $test)
    {
        return $this->baseTemplateForTests->contains($test);
    }

    /**
     * Set owner
     * @param User $user
     * @return ViewTemplate
     */
    public function setOwner($user)
    {
        $this->owner = $user;

        return $this;
    }

    /**
     * Get owner
     *
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    public function getEntityHash()
    {
        $json = json_encode(array(
            "name" => $this->getName(),
            "description" => $this->getDescription(),
            "head" => $this->getHead(),
            "css" => $this->getCss(),
            "js" => $this->getJs(),
            "html" => $this->getHtml()
        ));
        return sha1($json);
    }

    public function jsonSerialize(&$dependencies = array(), &$normalizedIdsMap = null)
    {
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
            "updatedOn" => $this->getUpdated()->getTimestamp(),
            "updatedBy" => $this->getUpdatedBy(),
            "lockedBy" => $this->getLockBy() ? $this->getLockBy()->getId() : null,
            "directLockBy" => $this->getDirectLockBy() ? $this->getDirectLockBy()->getId() : null,
            "owner" => $this->getOwner() ? $this->getOwner()->getId() : null,
            "groups" => $this->groups,
            "starterContent" => $this->starterContent
        );

        if ($normalizedIdsMap !== null) {
            $serialized["id"] = self::normalizeId("ViewTemplate", $serialized["id"], $normalizedIdsMap);
        }

        self::addDependency($dependencies, $serialized);
        return $serialized;
    }

}
