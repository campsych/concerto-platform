<?php

namespace Concerto\PanelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Table(name="ConcertoUser")
 * @ORM\Entity(repositoryClass="Concerto\PanelBundle\Repository\UserRepository")
 * @UniqueEntity(fields="username", message="validate.user.username.unique")
 * @UniqueEntity(fields="email", message="validate.user.email.unique")
 */
class User extends ATopEntity implements AdvancedUserInterface, \Serializable, \JsonSerializable, EquatableInterface
{

    const ROLE_SUPER_ADMIN = "ROLE_SUPER_ADMIN";
    const ROLE_TEST = "ROLE_TEST";
    const ROLE_TABLE = "ROLE_TABLE";
    const ROLE_TEMPLATE = "ROLE_TEMPLATE";
    const ROLE_WIZARD = "ROLE_WIZARD";
    const ROLE_FILE = "ROLE_FILE";

    /**
     * @var string
     * @Assert\Length(min="3", max="25", minMessage="validate.user.username.min", maxMessage="validate.user.username.max")
     * @Assert\NotBlank(message="validate.user.username.blank")
     * @ORM\Column(type="string", length=25, unique=true)
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=32)
     */
    private $salt;

    /**
     * @var string
     * @Assert\Length(min="4", max="40", minMessage="validate.user.password.min", maxMessage="validate.user.password.max")
     * @Assert\NotBlank(message="validate.user.password.blank", groups={"create"});
     * @ORM\Column(type="string", length=40)
     */
    private $password;

    /**
     *
     * @var string
     */
    private $passwordConfirmation;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=60, unique=true)
     * @Assert\Email(message="validate.user.email")
     * @Assert\Length(max="60",maxMessage="validate.user.email.max")
     * @Assert\NotBlank(message="validate.user.email.blank")
     */
    private $email;

    /**
     * @ORM\ManyToMany(targetEntity="Role", inversedBy="users")
     */
    private $roles;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->roles = new ArrayCollection();
        $this->salt = md5(uniqid(null, true));
    }

    /**
     * @inheritDoc
     */
    public function eraseCredentials()
    {

    }

    /**
     * @inheritDoc
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @inheritDoc
     */
    public function getRoles()
    {
        return $this->roles->toArray();
    }

    /**
     * @inheritDoc
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * @inheritDoc
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @see \Serializable::serialize()
     */
    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->username,
            $this->password,
            $this->salt
        ));
    }

    /**
     * @see \Serializable::unserialize()
     */
    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->username,
            $this->password,
            $this->salt
            ) = unserialize($serialized);
    }

    /**
     * Set username
     *
     * @param string $username
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Set salt
     *
     * @param string $salt
     * @return User
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Set password confirmation
     *
     * @param string $passwordConfirmation
     * @return User
     */
    public function setPasswordConfirmation($passwordConfirmation)
    {
        $this->passwordConfirmation = $passwordConfirmation;

        return $this;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Add roles
     *
     * @param Role $roles
     * @return User
     */
    public function addRole(Role $roles)
    {
        $this->roles[] = $roles;

        return $this;
    }

    /**
     * Remove roles
     *
     * @param Role $roles
     */
    public function removeRole(Role $roles)
    {
        $this->roles->removeElement($roles);
    }

    /**
     * Checks if the user has role
     *
     * @param Role $role
     * @return boolean
     */
    public function hasRole(Role $role)
    {
        return $this->roles->contains($role);
    }

    /**
     * Checks if the user has role
     *
     * @param string $role
     * @return boolean
     */
    public function hasRoleName($role_name)
    {
        foreach ($this->roles as $role) {
            if ($role->getName() == $role_name)
                return true;
        }
        return false;
    }

    /**
     * Checks if password is correct by comparing password and password confirmation
     *
     * @return boolean
     * @Assert\IsTrue(message = "validate.user.password.match", groups={"create"})
     */
    public function isPasswordCorrect()
    {
        return $this->password === $this->passwordConfirmation;
    }

    public function isAccountNonExpired()
    {
        return true;
    }

    public function isAccountNonLocked()
    {
        return true;
    }

    public function isCredentialsNonExpired()
    {
        return true;
    }

    public function isEnabled()
    {
        return !$this->archived;
    }

    public function __toString()
    {
        return "User (username:" . $this->getUsername() . ")";
    }

    public function getOwner()
    {
        return $this;
    }

    public function isEqualTo(UserInterface $user)
    {
        if ($this->password !== $user->getPassword()) {
            return false;
        }

        if ($this->salt !== $user->getSalt()) {
            return false;
        }

        if ($this->username !== $user->getUsername()) {
            return false;
        }

        return true;
    }

    public function jsonSerialize(&$dependencies = array())
    {
        return array(
            "class_name" => "User",
            "id" => $this->id,
            "accessibility" => $this->accessibility,
            "archived" => $this->archived ? "1" : "0",
            "username" => $this->username,
            "email" => $this->email,
            "updatedOn" => $this->updated->format("Y-m-d H:i:s"),
            "updatedBy" => $this->updatedBy,
            "role_super_admin" => $this->hasRoleName(self::ROLE_SUPER_ADMIN) ? "1" : "0",
            "role_test" => $this->hasRoleName(self::ROLE_TEST) ? "1" : "0",
            "role_template" => $this->hasRoleName(self::ROLE_TEMPLATE) ? "1" : "0",
            "role_table" => $this->hasRoleName(self::ROLE_TABLE) ? "1" : "0",
            "role_file" => $this->hasRoleName(self::ROLE_FILE) ? "1" : "0",
            "role_wizard" => $this->hasRoleName(self::ROLE_WIZARD) ? "1" : "0",
            "owner" => $this->getOwner() ? $this->getOwner()->getId() : null,
            "starterContent" => $this->starterContent,
            "groups" => $this->groups
        );
    }

}
