<?php

namespace App\Controller;

use App\Entity\Manga;
use App\Entity\ReadingList;
use App\Repository\ReadingListRepository;
use App\Repository\ReadingProgressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('', name: 'app_profile')]
    public function index(
        ReadingListRepository $readingListRepo,
        ReadingProgressRepository $progressRepo
    ): Response {
        $user = $this->getUser();

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'readingLists' => $readingListRepo->findByUser($user),
            'statusCounts' => $readingListRepo->countByStatus($user),
            'recentReads' => $progressRepo->findRecentByUser($user, 5),
            'statuses' => ReadingList::STATUSES,
        ]);
    }

    #[Route('/list/{status}', name: 'app_profile_list', methods: ['GET'])]
    public function listByStatus(string $status, ReadingListRepository $readingListRepo): Response
    {
        $user = $this->getUser();

        if (!array_key_exists($status, ReadingList::STATUSES)) {
            throw $this->createNotFoundException('Statut invalide');
        }

        return $this->render('profile/list.html.twig', [
            'entries' => $readingListRepo->findByUserAndStatus($user, $status),
            'currentStatus' => $status,
            'statusLabel' => ReadingList::STATUSES[$status],
            'statuses' => ReadingList::STATUSES,
            'statusCounts' => $readingListRepo->countByStatus($user),
        ]);
    }

    #[Route('/manga/{id}/add-to-list', name: 'app_add_to_list', methods: ['POST'])]
    public function addToList(
        Manga $manga,
        Request $request,
        ReadingListRepository $readingListRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        $status = $request->request->get('status', ReadingList::STATUS_TO_READ);

        $entry = $readingListRepo->findOneByUserAndManga($user, $manga);

        if ($entry) {
            $entry->setStatus($status);
        } else {
            $entry = new ReadingList();
            $entry->setUser($user);
            $entry->setManga($manga);
            $entry->setStatus($status);
            $em->persist($entry);
        }

        $em->flush();

        return $this->json([
            'success' => true,
            'status' => $entry->getStatus(),
            'label' => $entry->getStatusLabel(),
        ]);
    }

    #[Route('/manga/{id}/remove-from-list', name: 'app_remove_from_list', methods: ['POST'])]
    public function removeFromList(
        Manga $manga,
        ReadingListRepository $readingListRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        $entry = $readingListRepo->findOneByUserAndManga($user, $manga);

        if ($entry) {
            $em->remove($entry);
            $em->flush();
        }

        return $this->json(['success' => true]);
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
            'isFavorite' => $isFavorite,
        ]);
    }

    #[Route('/settings', name: 'app_profile_settings', methods: ['GET', 'POST'])]
    public function settings(Request $request, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            // Mise à jour du nom d'affichage
            $displayName = trim((string) $request->request->get('display_name', ''));
            if (!empty($displayName) && mb_strlen($displayName) <= 50) {
                $user->setDisplayName($displayName);
            }

            // Upload d'avatar
            $avatarFile = $request->files->get('avatar');
            if ($avatarFile) {
                $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $maxSize = 2 * 1024 * 1024; // 2 Mo

                if (!in_array($avatarFile->getMimeType(), $allowedMimes)) {
                    $this->addFlash('error', 'Format d\'image non supporté. Utilisez JPG, PNG, GIF ou WebP.');
                    return $this->redirectToRoute('app_profile_settings');
                }

                if ($avatarFile->getSize() > $maxSize) {
                    $this->addFlash('error', 'L\'image ne doit pas dépasser 2 Mo.');
                    return $this->redirectToRoute('app_profile_settings');
                }

                // Supprimer l'ancien avatar
                if ($user->getAvatarFilename()) {
                    $oldFile = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars/' . $user->getAvatarFilename();
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }

                // Sauvegarder le nouveau
                $newFilename = 'avatar_' . $user->getId() . '_' . uniqid() . '.' . $avatarFile->guessExtension();
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $avatarFile->move($uploadDir, $newFilename);
                $user->setAvatarFilename($newFilename);
            }

            $em->flush();
            $this->addFlash('success', 'Profil mis à jour avec succès !');

            return $this->redirectToRoute('app_profile_settings');
        }

        return $this->render('profile/settings.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/avatar/delete', name: 'app_profile_avatar_delete', methods: ['POST'])]
    public function deleteAvatar(EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($user->getAvatarFilename()) {
            $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars/' . $user->getAvatarFilename();
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $user->setAvatarFilename(null);
            $em->flush();
            $this->addFlash('success', 'Avatar supprimé.');
        }

        return $this->redirectToRoute('app_profile_settings');
    }
}
