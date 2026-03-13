<?php

namespace App\Repository;

use App\Entity\ReadingList;
use App\Entity\User;
use App\Entity\Manga;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReadingList>
 */
class ReadingListRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReadingList::class);
    }

    public function save(ReadingList $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ReadingList $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUserAndStatus(User $user, string $status): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->andWhere('r.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', $status)
            ->orderBy('r.addedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByUserAndManga(User $user, Manga $manga): ?ReadingList
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
     * Compte le nombre de mangas par statut pour un utilisateur.
     */
    public function countByStatus(User $user): array
    {
        $results = $this->createQueryBuilder('r')
            ->select('r.status, COUNT(r.id) as total')
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
            ->groupBy('r.status')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $row) {
            $counts[$row['status']] = $row['total'];
        }
        return $counts;
    }
}
