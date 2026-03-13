<?php

namespace App\Repository;

use App\Entity\Review;
use App\Entity\User;
use App\Entity\Manga;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    public function save(Review $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Review $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Récupère tous les avis d'un manga, triés par date (récent en premier).
     */
    public function findByManga(Manga $manga): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.manga = :manga')
            ->setParameter('manga', $manga)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les avis d'un utilisateur.
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère l'avis d'un utilisateur pour un manga donné.
     */
    public function findOneByUserAndManga(User $user, Manga $manga): ?Review
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->andWhere('r.manga = :manga')
            ->setParameter('user', $user)
            ->setParameter('manga', $manga)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Calcule la note moyenne d'un manga.
     */
    public function getAverageRating(Manga $manga): ?float
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.rating) as avgRating')
            ->andWhere('r.manga = :manga')
            ->setParameter('manga', $manga)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? round((float) $result, 1) : null;
    }

    /**
     * Compte le nombre d'avis pour un manga.
     */
    public function countByManga(Manga $manga): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.manga = :manga')
            ->setParameter('manga', $manga)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les derniers avis (tous mangas confondus).
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
