<?php

namespace App\Controller;

use App\Entity\Manga;
use App\Entity\Review;
use App\Repository\GenreRepository;
use App\Repository\MangaRepository;
use App\Repository\ReviewRepository;
use App\Security\Roles;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\MangaSearchService;
use App\Service\MangaHybridService;
use Symfony\Component\HttpFoundation\Request;

#[Route('/manga')]
class MangaController extends AbstractController
{
    public function __construct(
        private MangaHybridService $mangaService,
        private ReviewRepository $reviewRepository
    ) {}

    #[Route('/', name: 'app_manga_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = [
            'title' => $request->query->get('title', ''),
            'author' => $request->query->get('author', ''),
            'genre' => $request->query->get('genre', '')
        ];

        $hasSearch = array_filter($search, fn($value) => !empty($value));
        $mangas = $hasSearch ? $this->mangaService->searchManga($search) : $this->mangaService->getPopularMangas();

        return $this->render('manga/index.html.twig', [
            'mangas' => $mangas,
            'search' => $search,
            'can_edit' => $this->isGranted('ROLE_ADMIN')
        ]);
    }

    #[Route('/new', name: 'app_manga_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            try {
                $this->mangaService->createManga($data);
                $this->addFlash('success', 'Manga créé avec succès !');
                return $this->redirectToRoute('app_manga_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création : ' . $e->getMessage());
            }
        }

        return $this->render('manga/new.html.twig');
    }

    #[Route('/{id}', name: 'app_manga_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, MangaRepository $mangaRepository): Response
    {
        $manga = $this->mangaService->getManga($id);

        if (!$manga) {
            throw $this->createNotFoundException('Manga non trouvé');
        }

        // Charger les avis du manga
        $mangaEntity = $mangaRepository->find($id);
        $reviews = [];
        $averageRating = null;
        $userReview = null;

        if ($mangaEntity) {
            $reviews = $this->reviewRepository->findByManga($mangaEntity);
            $averageRating = $this->reviewRepository->getAverageRating($mangaEntity);

            if ($this->getUser()) {
                $userReview = $this->reviewRepository->findOneByUserAndManga($this->getUser(), $mangaEntity);
            }
        }

        return $this->render('manga/show.html.twig', [
            'manga' => $manga,
            'can_edit' => $this->isGranted('ROLE_ADMIN'),
            'reviews' => $reviews,
            'averageRating' => $averageRating,
            'reviewCount' => count($reviews),
            'userReview' => $userReview,
        ]);
    }

    #[Route('/{id}/review', name: 'app_manga_review', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function addReview(int $id, Request $request, MangaRepository $mangaRepository): Response
    {
        $manga = $mangaRepository->find($id);

        if (!$manga) {
            throw $this->createNotFoundException('Manga non trouvé');
        }

        if (!$this->isCsrfTokenValid('review' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_manga_show', ['id' => $id]);
        }

        $rating = (int) $request->request->get('rating', 5);
        $comment = trim((string) $request->request->get('comment', ''));

        if ($rating < 1 || $rating > 5) {
            $this->addFlash('error', 'La note doit être entre 1 et 5.');
            return $this->redirectToRoute('app_manga_show', ['id' => $id]);
        }

        if (empty($comment)) {
            $this->addFlash('error', 'Le commentaire ne peut pas être vide.');
            return $this->redirectToRoute('app_manga_show', ['id' => $id]);
        }

        if (mb_strlen($comment) > 2000) {
            $this->addFlash('error', 'Le commentaire ne doit pas dépasser 2000 caractères.');
            return $this->redirectToRoute('app_manga_show', ['id' => $id]);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Vérifier si l'utilisateur a déjà laissé un avis
        $existingReview = $this->reviewRepository->findOneByUserAndManga($user, $manga);

        if ($existingReview) {
            // Mettre à jour l'avis existant
            $existingReview->setRating($rating);
            $existingReview->setComment($comment);
            $this->reviewRepository->save($existingReview, true);
            $this->addFlash('success', 'Votre avis a été mis à jour !');
        } else {
            // Créer un nouvel avis
            $review = new Review();
            $review->setUser($user);
            $review->setManga($manga);
            $review->setRating($rating);
            $review->setComment($comment);
            $this->reviewRepository->save($review, true);
            $this->addFlash('success', 'Votre avis a été ajouté !');
        }

        return $this->redirectToRoute('app_manga_show', ['id' => $id]);
    }

    #[Route('/{id}/review/delete', name: 'app_manga_review_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function deleteReview(int $id, Request $request, MangaRepository $mangaRepository): Response
    {
        $manga = $mangaRepository->find($id);

        if (!$manga) {
            throw $this->createNotFoundException('Manga non trouvé');
        }

        if (!$this->isCsrfTokenValid('delete_review' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_manga_show', ['id' => $id]);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $review = $this->reviewRepository->findOneByUserAndManga($user, $manga);

        if ($review) {
            $this->reviewRepository->remove($review, true);
            $this->addFlash('success', 'Votre avis a été supprimé.');
        }

        return $this->redirectToRoute('app_manga_show', ['id' => $id]);
    }

    #[Route('/{id}/edit', name: 'app_manga_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(int $id, Request $request): Response
    {
        try {
            $manga = $this->mangaService->getManga($id);
            
            if ($request->isMethod('POST')) {
                $data = $request->request->all();
                $this->mangaService->updateManga($id, $data);
                
                $this->addFlash('success', 'Manga mis à jour avec succès !');
                return $this->redirectToRoute('app_manga_show', ['id' => $id]);
            }

            return $this->render('manga/edit.html.twig', [
                'manga' => $manga
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur : ' . $e->getMessage());
            return $this->redirectToRoute('app_manga_index');
        }
    }

    #[Route('/{id}/delete', name: 'app_manga_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete'.$id, $request->request->get('_token'))) {
            try {
                $this->mangaService->deleteManga($id);
                $this->addFlash('success', 'Manga supprimé avec succès !');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_manga_index');
    }

    #[Route('/category/{name}', name: 'app_manga_by_category')]
    public function byCategory(string $name, GenreRepository $genreRepository): Response
    {
        $genre = $genreRepository->findOneBy(['name' => $name]);

        if (!$genre) {
            throw $this->createNotFoundException('Genre not found');
        }

        return $this->render('manga/category.html.twig', [
            'category' => $name,
            'mangas' => $genre->getMangas(),
            'can_edit' => $this->isGranted(Roles::ROLE_ADMIN)
        ]);
    }

    #[Route('/details/{id}', name: 'app_manga_details')]
    public function details(string $id, MangaSearchService $searchService): Response
    {
        $manga = $searchService->getMangaDetails($id);

        if (!$manga) {
            throw $this->createNotFoundException('Manga non trouvé');
        }

        return $this->render('manga/details.html.twig', [
            'manga' => $manga
        ]);
    }
}
