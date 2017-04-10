<?php

namespace Concerto\APIBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\OAuthServerBundle\Entity\Client as BaseClient;
use \Doctrine\Common\Collections\ArrayCollection;

/**
 * Client
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Concerto\APIBundle\Repository\ClientRepository")
 */
class Client extends BaseClient implements \JsonSerializable {

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="AccessToken", mappedBy="client", cascade={"remove"})
     */
    private $accessTokens;

    public function __construct() {
        parent::__construct();

        $this->accessTokens = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId() {
        return $this->id;
    }

    public function jsonSerialize() {
        return array(
            "class_name" => "Client",
            "id" => $this->getId(),
            "randomId" => $this->getRandomId(),
            "fullId" => $this->getId() . "_" . $this->getRandomId(),
            "secret" => $this->getSecret()
        );
    }

}
