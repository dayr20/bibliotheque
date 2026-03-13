<?php

namespace App\Repository;

use App\Entity\ReadingProgress;
use App\Entity\User;
use App\Entity\Manga;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReadingProgress>
 */
class ReadingProgressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReadingProgress::class);
    }

    public function save(ReadingProgress $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByUserAndManga(User $user, Manga $manga): ?ReadingProgress
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
     * Récupère les dernières lectures d'un utilisateur.
     */
    public function findRecentByUser(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.lastReadAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
