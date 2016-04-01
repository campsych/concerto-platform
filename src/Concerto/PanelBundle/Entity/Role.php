<?php

namespace Concerto\PanelBundle\Entity;

use Symfony\Component\Security\Core\Role\RoleInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

/**
 * @ORM\Table
 * @ORM\Entity
 */
class Role extends AEntity implements RoleInterface {

    /**
     * @ORM\Column(name="name", type="string", length=30)
     */
    private $name;

    /**
     * @ORM\Column(name="role", type="string", length=20, unique=true)
     */
    private $role;

    /**
     * @ORM\ManyToMany(targetEntity="User", mappedBy="roles")
     */
    private $users;

    public function __construct() {
        parent::__construct();
        
        $this->users = new ArrayCollection();
    }

    /**
     * @see RoleInterface
     */
    public function getRole() {
        return $this->role;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Role
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
     * Set role
     *
     * @param string $role
     * @return Role
     */
    public function setRole($role) {
        $this->role = $role;

        return $this;
    }

    /**
     * Add users
     *
     * @param User $users
     * @return Role
     */
    public function addUser(User $users) {
        $this->users[] = $users;

        return $this;
    }

    /**
     * Remove users
     *
     * @param User $users
     */
    public function removeUser(User $users) {
        $this->users->removeElement($users);
    }

    /**
     * Get users
     *
     * @return Collection 
     */
    public function getUsers() {
        return $this->users;
    }

}
