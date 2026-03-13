<?php

namespace App\Controller;

use App\Service\MangaSearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    #[Route('/search', name: 'app_search', methods: ['GET'])]
    public function search(Request $request, MangaSearchService $searchService): Response
    {
        $query = $request->query->get('q');
        $results = [];

        if ($query) {
            $results = $searchService->searchManga($query);
        }

        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            return $this->json([
                'results' => $results
            ]);
        }

        return $this->render('search/index.html.twig', [
            'query' => $query,
            'results' => $results
        ]);
    }
} 