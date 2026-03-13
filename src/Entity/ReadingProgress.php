<?php

namespace App\Entity;

use App\Repository\ReadingProgressRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReadingProgressRepository::class)]
class ReadingProgress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Manga::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Manga $manga = null;

    #[ORM\ManyToOne(targetEntity: Chapter::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Chapter $lastChapter = null;

    #[ORM\Column]
    private \DateTimeImmutable $lastReadAt;

    public function __construct()
    {
        $this->lastReadAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getManga(): ?Manga
    {
        return $this->manga;
    }

    public function setManga(?Manga $manga): static
    {
        $this->manga = $manga;
        return $this;
    }

    public function getLastChapter(): ?Chapter
    {
        return $this->lastChapter;
    }

    public function setLastChapter(?Chapter $chapter): static
    {
        $this->lastChapter = $chapter;
        $this->lastReadAt = new \DateTimeImmutable();
        return $this;
    }

    public function getLastReadAt(): \DateTimeImmutable
    {
        return $this->lastReadAt;
    }
}
