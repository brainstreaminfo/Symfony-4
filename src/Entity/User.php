<?php

namespace App\Entity;

use App\Services\NotifiableInterface;
use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Annotation\Notifiable;


/**
 * Class User
 * @package App\Entity
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="tbl_user")
 * @UniqueEntity(fields="email", message="Email already taken")
 * @UniqueEntity(fields="username", message="Username already taken")
 * @Notifiable(name="tbl_user")
 */
class User extends BaseUser implements NotifiableInterface
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @Assert\Email(
     *     message = "The email '{{ value }}' is not a valid email."
     * )
     * @Assert\NotBlank()
     */
    protected $email;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(
     *      min = 5,
     *      minMessage = "Your Username must be at least {{ limit }} characters long"
     * )
     * @Assert\Regex(
     *     pattern     = "/^[a-zA-Z][a-zA-Z0-9]+$/i",
     *     message="Username must Alphanumeric and start with alphabets"
     * )
     */
    protected $username;

    /**
     * @var boolean
     *
     * @ORM\Column(name="locked", type="boolean", nullable=false, options={"default": false})
     */
    private $locked = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="expired", type="boolean", nullable=false, options={"default": false})
     */
    private $expired =  false;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expires_at", type="datetime", nullable=true)
     */
    private $expiresAt;

    /**
     * @var boolean
     *
     * @ORM\Column(name="credentials_expired", type="boolean", nullable=false, options={"default": false})
     */
    private $credentialsExpired = false;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="credentials_expire_at", type="datetime", nullable=true)
     */
    private $credentialsExpireAt;

    /**
     * @var \App\Entity\Client
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Client")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     * })
     */
    private $client;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="profile_picture", type="string", length=255, nullable=true)
     * @Assert\File(
     *     maxSize="10M",
     *     mimeTypes = { "image/png", "image/jpeg", "image/jpg" }
     *     )
     */
    private $profilePicture;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_created", type="datetime", nullable=true)
     */
    private $dateCreated;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_updated", type="datetime", nullable=true)
     */
    private $dateUpdated;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="created_by", referencedColumnName="id")
     * })
     */
    private $createdBy;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="updated_by", referencedColumnName="id")
     * })
     */
    private $updatedBy;

    /**
     * @var array
     *
     * @ORM\Column(name="location", type="simple_array", nullable=true, options={"default" : null})
     */
    private $location;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get location
     *
     * @return array
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set location
     * @param string $location
     *
     * @return User
     */
    public function setLocation(array $location)
    {
        $this->location = [];

        foreach($location as $object) {
            $this->location[] = $object->getId();
        }

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
     * Set name
     * @param string $name
     *
     * @return User
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get profilePicture
     *
     * @return string
     */
    public function getProfilePicture()
    {
        return $this->profilePicture;
    }

    /**
     * Set profilePicture
     * @param string $profilePicture
     *
     * @return User
     */
    public function setProfilePicture($profilePicture)
    {
        $this->profilePicture = $profilePicture;

        return $this;
    }

    /**
     * Get dateCreated
     *
     * @return \DateTime
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * Set dateCreated
     * @param \DateTime $dateCreated
     *
     * @return User
     */
    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * Get dateUpdated
     *
     * @return \DateTime
     */
    public function getDateUpdated()
    {
        return $this->dateUpdated;
    }

    /**
     * Set dateUpdated
     * @param \DateTime $dateUpdated
     *
     * @return User
     */
    public function setDateUpdated($dateUpdated)
    {
        $this->dateUpdated = $dateUpdated;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return integer
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set createdBy
     * @param User $createdBy
     *
     * @return Franchise
     */
    public function setCreatedBy(User $createdBy = null)
    {
        $this->createdBy = $createdBy;

        return $this;
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

    /**
     * Get client
     *
     * @return \App\Entity\Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Set client
     * @param \App\Entity\Client $client
     *
     * @return User
     */
    public function setClient(\App\Entity\Client $client = null)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Gets triggered only on insert
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->dateCreated = new \DateTime("now");
    }

    /**
     * Gets triggered only on update
     * @ORM\PreUpdate
     */
    public function onPreUpdate()
    {
        $this->dateUpdated = new \DateTime("now");
    }

    /**
     * Get updatedBy
     *
     * @return User
     */
    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }

    /**
     * Set updatedBy
     * @param User $updatedBy
     *
     * @return Client
     */
    public function setUpdatedBy(User $updatedBy = null)
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }
}