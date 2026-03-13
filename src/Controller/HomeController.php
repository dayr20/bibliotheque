<?php

namespace App\Controller;

use App\Service\MangaHybridService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private MangaHybridService $mangaService
    ) {}

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        $popularMangas = $this->mangaService->getPopularMangas(6);
        $newMangas = $this->mangaService->getNewMangas(6);
        $recommendedMangas = $this->mangaService->getRecommendedMangas(6);

        return $this->render('home/index.html.twig', [
            'popularMangas' => $popularMangas,
            'newMangas' => $newMangas,
            'recommendedMangas' => $recommendedMangas,
        ]);
    }
}
