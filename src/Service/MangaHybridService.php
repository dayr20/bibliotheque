<?php

namespace App\Service;

use App\Entity\Genre;
use App\Entity\Manga;
use App\Repository\MangaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

class MangaHybridService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private MangaRepository $mangaRepository,
        private MangaDexService $mangaDexService
    ) {}

    public function getManga(int $id): ?Manga
    {
        return $this->mangaRepository->find($id);
    }

    public function createManga(array $data): Manga
    {
        $manga = new Manga();
        $this->updateMangaData($manga, $data);
        
        $this->entityManager->persist($manga);
        $this->entityManager->flush();
        
        return $manga;
    }

    public function updateManga(int $id, array $data): Manga
    {
        $manga = $this->getManga($id);
        
        if (!$manga) {
            throw new \Exception('Manga non trouvé');
        }
        
        $this->updateMangaData($manga, $data);
        $this->entityManager->flush();
        
        return $manga;
    }

    public function deleteManga(int $id): void
    {
        $manga = $this->getManga($id);
        
        if (!$manga) {
            throw new \Exception('Manga non trouvé');
        }
        
        $this->entityManager->remove($manga);
        $this->entityManager->flush();
    }

    private function updateMangaData(Manga $manga, array $data): void
    {
        if (isset($data['title'])) {
            $manga->setTitle($data['title']);
        }
        if (isset($data['author'])) {
            $manga->setAuthor($data['author']);
        }
        if (isset($data['description'])) {
            $manga->setDescription($data['description']);
        }
        if (isset($data['coverImage'])) {
            $manga->setCoverImage($data['coverImage']);
        }
        if (isset($data['rating'])) {
            $manga->setRating((float) $data['rating']);
        }
        if (isset($data['isNew'])) {
            $manga->setIsNew((bool) $data['isNew']);
        }
    }

    public function getPopularMangas(int $limit = 6): array
    {
        return $this->mangaRepository->findBy([], ['rating' => 'DESC'], $limit);
    }

    public function getNewMangas(int $limit = 6): array
    {
        return $this->mangaRepository->findBy(['isNew' => true], ['id' => 'DESC'], $limit);
    }

    public function getRecommendedMangas(int $limit = 6): array
    {
        // Pour l'instant, on retourne simplement les mangas populaires
        return $this->getPopularMangas($limit);
    }

    public function searchManga(array $criteria): array
    {
        $title = $criteria['title'] ?? '';
        $author = $criteria['author'] ?? '';
        
        if (empty($title) && empty($author)) {
            return $this->getPopularMangas(20);
        }
        
        $qb = $this->mangaRepository->createQueryBuilder('m');
        
        if (!empty($title)) {
            $qb->andWhere('m.title LIKE :title')
               ->setParameter('title', '%' . $title . '%');
        }
        
        if (!empty($author)) {
            $qb->andWhere('m.author LIKE :author')
               ->setParameter('author', '%' . $author . '%');
        }
        
        return $qb->getQuery()->getResult();
    }
} 