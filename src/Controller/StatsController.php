<?php

namespace App\Controller;

use App\Repository\ReadingListRepository;
use App\Repository\ReadingProgressRepository;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class StatsController extends AbstractController
{
    #[Route('/stats', name: 'app_profile_stats')]
    public function index(
        ReadingListRepository $readingListRepo,
        ReadingProgressRepository $progressRepo,
        ReviewRepository $reviewRepo,
        EntityManagerInterface $em
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Comptage par statut
        $statusCounts = $readingListRepo->countByStatus($user);

        // Total mangas lus
        $totalRead = ($statusCounts['completed'] ?? 0);
        $totalReading = ($statusCounts['reading'] ?? 0);
        $totalToRead = ($statusCounts['to_read'] ?? 0);
        $totalDropped = ($statusCounts['dropped'] ?? 0);
        $totalInList = $totalRead + $totalReading + $totalToRead + $totalDropped;

        // Nombre d'avis
        $userReviews = $reviewRepo->findByUser($user);
        $totalReviews = count($userReviews);

        // Note moyenne donnée
        $avgRatingGiven = 0;
        if ($totalReviews > 0) {
            $sum = array_sum(array_map(fn($r) => $r->getRating(), $userReviews));
            $avgRatingGiven = round($sum / $totalReviews, 1);
        }

        // Favoris
        $totalFavorites = $user->getFavoriteMangas()->count();

        // Genres préférés (via les mangas dans la reading list)
        $genreStats = [];
        $readingList = $readingListRepo->findByUser($user);
        foreach ($readingList as $entry) {
            $manga = $entry->getManga();
            if ($manga) {
                foreach ($manga->getGenres() as $genre) {
                    $name = $genre->getName();
                    $genreStats[$name] = ($genreStats[$name] ?? 0) + 1;
                }
            }
        }
        arsort($genreStats);
        $topGenres = array_slice($genreStats, 0, 8, true);

        // Distribution des notes données
        $ratingDistribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($userReviews as $review) {
            $ratingDistribution[$review->getRating()]++;
        }

        return $this->render('profile/stats.html.twig', [
            'totalRead' => $totalRead,
            'totalReading' => $totalReading,
            'totalToRead' => $totalToRead,
            'totalDropped' => $totalDropped,
            'totalInList' => $totalInList,
            'totalReviews' => $totalReviews,
            'avgRatingGiven' => $avgRatingGiven,
            'totalFavorites' => $totalFavorites,
            'topGenres' => $topGenres,
            'ratingDistribution' => $ratingDistribution,
            'statusCounts' => $statusCounts,
        ]);
    }

    #[Route('/stats/data', name: 'app_profile_stats_data', methods: ['GET'])]
    public function statsData(
        ReadingListRepository $readingListRepo,
        ReviewRepository $reviewRepo
    ): JsonResponse {
        $user = $this->getUser();
        $statusCounts = $readingListRepo->countByStatus($user);

        $userReviews = $reviewRepo->findByUser($user);
        $ratingDistribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($userReviews as $review) {
            $ratingDistribution[$review->getRating()]++;
        }

        $genreStats = [];
        foreach ($readingListRepo->findByUser($user) as $entry) {
            $manga = $entry->getManga();
            if ($manga) {
                foreach ($manga->getGenres() as $genre) {
                    $name = $genre->getName();
                    $genreStats[$name] = ($genreStats[$name] ?? 0) + 1;
                }
            }
        }
        arsort($genreStats);

        return $this->json([
            'statusCounts' => $statusCounts,
            'ratingDistribution' => $ratingDistribution,
            'genreStats' => array_slice($genreStats, 0, 8, true),
        ]);
    }
}
