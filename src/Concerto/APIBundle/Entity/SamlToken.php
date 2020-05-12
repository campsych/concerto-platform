<?php

namespace Concerto\APIBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AccessToken
 *
 * @ORM\Table(indexes={@ORM\Index(name="saml_token_hash_idx", columns={"hash"})})
 * @ORM\Entity(repositoryClass="Concerto\APIBundle\Repository\SamlTokenRepository")
 */
class SamlToken
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $attributes;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $nameId;

    /**
     * @ORM\Column(type="string", length=40)
     */
    private $hash;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $expiresAt;

    /**
     * @ORM\Column(type="boolean")
     */
    private $revoked;

    public function __construct()
    {
        $this->revoked = false;
        $this->generateHash();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    private function generateHash()
    {
        $this->hash = sha1(random_bytes(20));
    }

    /**
     * @return string
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param string $attributes
     * @return SamlToken
     */
    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * @return string
     */
    public function getNameId()
    {
        return $this->nameId;
    }

    /**
     * @param string $nameId
     * @return SamlToken
     */
    public function setNameId($nameId)
    {
        $this->nameId = $nameId;
        return $this;
    }

    /**
     * @return integer
     */
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    /**
     * @param integer $expiresAt
     * @return SamlToken
     */
    public function setExpiresAt($expiresAt)
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    /**
     * @return bool
     */
    public function isRevoked(): bool
    {
        return $this->revoked;
    }

    /**
     * @param bool $revoked
     * @return SamlToken
     */
    public function setRevoked(bool $revoked)
    {
        $this->revoked = $revoked;
        return $this;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     * @return SamlToken
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
        return $this;
    }
}
