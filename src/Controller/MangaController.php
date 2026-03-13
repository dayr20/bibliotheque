<?php

namespace App\Controller;

use App\Entity\Manga;
use App\Repository\GenreRepository;
use App\Repository\MangaRepository;
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
        private MangaHybridService $mangaService
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

    #[Route('/{id}', name: 'app_manga_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $manga = $this->mangaService->getManga($id);
        
        if (!$manga) {
            throw $this->createNotFoundException('Manga non trouvé');
        }

        return $this->render('manga/show.html.twig', [
            'manga' => $manga,
            'can_edit' => $this->isGranted('ROLE_ADMIN')
        ]);
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
