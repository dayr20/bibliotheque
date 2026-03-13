<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[MongoDB\Document]
class Manga
{
    #[MongoDB\Id]
    private $id;

    #[MongoDB\Field(type: 'string')]
    private $title;

    #[MongoDB\Field(type: 'string')]
    private $author;

    #[MongoDB\Field(type: 'string')]
    private $coverImage;

    #[MongoDB\Field(type: 'float')]
    private $rating;

    #[MongoDB\Field(type: 'bool')]
    private $isNew = false;

    #[MongoDB\Field(type: 'string')]
    private $description;

    #[MongoDB\Field(type: 'collection')]
    private $genres = [];

    #[MongoDB\EmbedMany(targetDocument: Chapter::class)]
    private $chapters;

    #[MongoDB\Field(type: 'date')]
    private $createdAt;

    #[MongoDB\Field(type: 'date')]
    private $updatedAt;

    #[MongoDB\Field(type: 'hash')]
    private $stats = [
        'views' => 0,
        'favorites' => 0,
        'rating_count' => 0,
        'total_rating' => 0
    ];

    public function __construct()
    {
        $this->chapters = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    // Getters and Setters

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(string $author): self
    {
        $this->author = $author;
        return $this;
    }

    public function getCoverImage(): ?string
    {
        return $this->coverImage;
    }

    public function setCoverImage(?string $coverImage): self
    {
        $this->coverImage = $coverImage;
        return $this;
    }

    public function getRating(): ?float
    {
        return $this->rating;
    }

    public function setRating(float $rating): self
    {
        $this->rating = $rating;
        return $this;
    }

    public function getIsNew(): bool
    {
        return $this->isNew;
    }

    public function setIsNew(bool $isNew): self
    {
        $this->isNew = $isNew;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getGenres(): array
    {
        return $this->genres;
    }

    public function setGenres(array $genres): self
    {
        $this->genres = $genres;
        return $this;
    }

    public function getChapters(): Collection
    {
        return $this->chapters;
    }

    public function addChapter(Chapter $chapter): self
    {
        if (!$this->chapters->contains($chapter)) {
            $this->chapters->add($chapter);
        }
        return $this;
    }

    public function removeChapter(Chapter $chapter): self
    {
        $this->chapters->removeElement($chapter);
        return $this;
    }

    public function getStats(): array
    {
        return $this->stats;
    }

    public function incrementStat(string $stat, int $value = 1): self
    {
        if (isset($this->stats[$stat])) {
            $this->stats[$stat] += $value;
        }
        return $this;
    }

    #[MongoDB\PreUpdate]
    public function updateTimestamp()
    {
        $this->updatedAt = new \DateTime();
    }
} 