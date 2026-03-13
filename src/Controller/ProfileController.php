<?php

namespace App\Controller;

use App\Entity\Manga;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('', name: 'app_profile')]
    public function index(): Response
    {
        return $this->render('profile/index.html.twig', [
            'user' => $this->getUser()
        ]);
    }

    #[Route('/manga/{id}/toggle-favorite', name: 'app_toggle_favorite_manga', methods: ['POST'])]
    public function toggleFavoriteManga(Manga $manga, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        
        if ($user->isMangaFavorite($manga)) {
            $user->removeFavoriteManga($manga);
            $isFavorite = false;
        } else {
            $user->addFavoriteManga($manga);
            $isFavorite = true;
        }
        
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'isFavorite' => $isFavorite
        ]);
    }
} 