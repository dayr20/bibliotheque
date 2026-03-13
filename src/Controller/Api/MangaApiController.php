<?php

namespace App\Controller\Api;

use App\Repository\MangaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1')]
class MangaApiController extends AbstractController
{
    public function __construct(
        private MangaRepository $mangaRepository
    ) {}

    #[Route('/mangas', name: 'api_manga_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
        $offset = ($page - 1) * $limit;

        $title = $request->query->get('title', '');
        $author = $request->query->get('author', '');

        $qb = $this->mangaRepository->createQueryBuilder('m');

        if (!empty($title)) {
            $qb->andWhere('m.title LIKE :title')
               ->setParameter('title', '%' . $title . '%');
        }

        if (!empty($author)) {
            $qb->andWhere('m.author LIKE :author')
               ->setParameter('author', '%' . $author . '%');
        }

        // Count total
        $countQb = clone $qb;
        $total = $countQb->select('COUNT(m.id)')->getQuery()->getSingleScalarResult();

        // Fetch paginated results
        $mangas = $qb->orderBy('m.rating', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->json([
            'data' => array_map([$this, 'serializeManga'], $mangas),
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int) $total,
                'pages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    #[Route('/mangas/{id}', name: 'api_manga_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $manga = $this->mangaRepository->find($id);

        if (!$manga) {
            return $this->json(['error' => 'Manga non trouvé'], 404);
        }

        $data = $this->serializeManga($manga);
        $data['chapters'] = [];

        foreach ($manga->getChapters() as $chapter) {
            $data['chapters'][] = [
                'id' => $chapter->getId(),
                'number' => $chapter->getNumber(),
                'title' => $chapter->getTitle(),
                'created_at' => $chapter->getCreatedAt()?->format('c'),
            ];
        }

        return $this->json(['data' => $data]);
    }

    #[Route('/mangas/popular', name: 'api_manga_popular', methods: ['GET'])]
    public function popular(Request $request): JsonResponse
    {
        $limit = min(50, max(1, (int) $request->query->get('limit', 10)));

        $mangas = $this->mangaRepository->createQueryBuilder('m')
            ->orderBy('m.rating', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->json([
            'data' => array_map([$this, 'serializeManga'], $mangas),
        ]);
    }

    #[Route('/mangas/search', name: 'api_manga_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 12)));
        $offset = ($page - 1) * $limit;

        $qb = $this->mangaRepository->createQueryBuilder('m');

        $title = $request->query->get('title', '');
        $author = $request->query->get('author', '');
        $status = $request->query->get('status', '');
        $year = $request->query->get('year', '');
        $ratingMin = $request->query->get('rating_min', '');
        $sort = $request->query->get('sort', 'rating');

        if (!empty($title)) {
            $qb->andWhere('m.title LIKE :title')->setParameter('title', '%' . $title . '%');
        }
        if (!empty($author)) {
            $qb->andWhere('m.author LIKE :author')->setParameter('author', '%' . $author . '%');
        }
        if (!empty($status)) {
            $qb->andWhere('m.status = :status')->setParameter('status', $status);
        }
        if (!empty($year)) {
            $qb->andWhere('m.year = :year')->setParameter('year', (int) $year);
        }
        if (!empty($ratingMin)) {
            $qb->andWhere('m.rating >= :ratingMin')->setParameter('ratingMin', (float) $ratingMin);
        }

        $countQb = clone $qb;
        $total = $countQb->select('COUNT(m.id)')->getQuery()->getSingleScalarResult();

        match ($sort) {
            'title' => $qb->orderBy('m.title', 'ASC'),
            'year' => $qb->orderBy('m.year', 'DESC'),
            'newest' => $qb->orderBy('m.isNew', 'DESC')->addOrderBy('m.id', 'DESC'),
            default => $qb->orderBy('m.rating', 'DESC'),
        };

        $mangas = $qb->setFirstResult($offset)->setMaxResults($limit)->getQuery()->getResult();

        return $this->json([
            'data' => array_map([$this, 'serializeManga'], $mangas),
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int) $total,
                'pages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    private function serializeManga($manga): array
    {
        $genres = [];
        foreach ($manga->getGenres() as $genre) {
            $genres[] = $genre->getName();
        }

        return [
            'id' => $manga->getId(),
            'title' => $manga->getTitle(),
            'author' => $manga->getAuthor(),
            'description' => $manga->getDescription(),
            'cover_image' => $manga->getCoverImage(),
            'rating' => $manga->getRating(),
            'is_new' => $manga->isNew(),
            'status' => $manga->getStatus(),
            'year' => $manga->getYear(),
            'genres' => $genres,
        ];
    }
}
