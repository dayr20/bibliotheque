<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $verificationCode = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $verificationCodeExpiresAt = null;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\ManyToMany(targetEntity: Manga::class)]
    #[ORM\JoinTable(name: 'user_favorite_manga')]
    private Collection $favoriteMangas;

    public function __construct()
    {
        $this->favoriteMangas = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function getVerificationCode(): ?string
    {
        return $this->verificationCode;
    }

    public function setVerificationCode(?string $verificationCode): static
    {
        $this->verificationCode = $verificationCode;
        return $this;
    }

    public function getVerificationCodeExpiresAt(): ?\DateTimeImmutable
    {
        return $this->verificationCodeExpiresAt;
    }

    public function setVerificationCodeExpiresAt(?\DateTimeImmutable $expiresAt): static
    {
        $this->verificationCodeExpiresAt = $expiresAt;
        return $this;
    }

    public function isVerificationCodeExpired(): bool
    {
        if ($this->verificationCodeExpiresAt === null) {
            return true;
        }
        return $this->verificationCodeExpiresAt < new \DateTimeImmutable();
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    /**
     * @return Collection<int, Manga>
     */
    public function getFavoriteMangas(): Collection
    {
        return $this->favoriteMangas;
    }

    public function addFavoriteManga(Manga $manga): static
    {
        if (!$this->favoriteMangas->contains($manga)) {
            $this->favoriteMangas->add($manga);
        }
        return $this;
    }

    public function removeFavoriteManga(Manga $manga): static
    {
        $this->favoriteMangas->removeElement($manga);
        return $this;
    }

    public function isMangaFavorite(Manga $manga): bool
    {
        return $this->favoriteMangas->contains($manga);
    }
} 