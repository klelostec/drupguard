<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\Table(name="`user`")
 * @UniqueEntity(fields={"username"}, message="There is already an account with this username")
 * @UniqueEntity(fields={"email"}, message="There is already an account with this email")
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"show_project", "list_projects"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     * @Assert\NotBlank(groups={"registration", "user_admin"})
     */
    private $username;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @var string The plain password
     * @Assert\NotBlank(groups={"registration", "user_admin_add", "profile_password", "change_password"})
     * @Assert\Length(
     *     min=6,
     *     max=4096,
     *     minMessage="Your password should be at least {{ limit }} characters",
     *     groups={"registration", "user_admin_add", "profile_password", "change_password"}
     * )
     */
    private $plainPassword;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     * @Assert\Email(groups={"registration", "user_admin", "profile"})
     * @Assert\NotBlank(groups={"registration", "user_admin", "profile"})
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(groups={"registration", "user_admin", "profile"})
     */
    private $firstname;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(groups={"registration", "user_admin", "profile"})
     */
    private $lastname;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isVerified = false;

    /**
     * @ORM\ManyToMany(targetEntity=Project::class, mappedBy="allowedUsers")
     */
    private $allowedProjects;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $token_api;

    public function __construct()
    {
        $this->allowedProjects = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        if (($key = array_search('ROLE_USER', $roles)) !== false) {
            unset($roles[$key]);
            $roles = array_values($roles);
        }
        $this->roles = $roles;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    /**
     * @return Collection|Project[]
     */
    public function getAllowedProjects(): Collection
    {
        return $this->allowedProjects;
    }

    public function addAllowedProject(Project $allowedProject): self
    {
        if (!$this->allowedProjects->contains($allowedProject)) {
            $this->allowedProjects[] = $allowedProject;
            $allowedProject->addAllowedUser($this);
        }

        return $this;
    }

    public function removeAllowedProject(Project $allowedProject): self
    {
        if ($this->allowedProjects->removeElement($allowedProject)) {
            $allowedProject->removeAllowedUser($this);
        }

        return $this;
    }

    public function isSuperAdmin()
    {
        return $this->getId() ===1;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getTokenApi(): ?string
    {
        return $this->token_api;
    }

    public function setTokenApi(?string $token_api): self
    {
        $this->token_api = $token_api;

        return $this;
    }

    public function __toString()
    {
        return $this->getFirstname() . ' ' . $this->getLastname();
    }
}
