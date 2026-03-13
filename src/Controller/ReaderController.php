<?php

namespace App\Controller;

use App\Entity\Chapter;
use App\Entity\Manga;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ReaderController extends AbstractController
{
    #[Route('/manga/{id}/read', name: 'app_manga_read')]
    public function read(Request $request, Manga $manga, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté pour lire les mangas.');
            return $this->redirectToRoute('app_login');
        }

        // Récupérer tous les chapitres du manga
        $chapters = $entityManager->getRepository(Chapter::class)->findByMangaOrderedByNumber($manga);

        if (empty($chapters)) {
            $this->addFlash('info', 'Aucun chapitre n\'est disponible pour ce manga.');
            return $this->redirectToRoute('app_manga_show', ['id' => $manga->getId()]);
        }

        // Récupérer le chapitre demandé ou le premier chapitre par défaut
        $currentChapter = null;
        $chapterId = $request->query->get('chapter');
        
        if ($chapterId) {
            $currentChapter = $entityManager->getRepository(Chapter::class)->find($chapterId);
        }
        
        if (!$currentChapter) {
            $currentChapter = $chapters[0];
        }

        // Trouver les chapitres précédent et suivant
        $prevChapter = null;
        $nextChapter = null;
        foreach ($chapters as $index => $chapter) {
            if ($chapter->getId() === $currentChapter->getId()) {
                if ($index > 0) {
                    $prevChapter = $chapters[$index - 1];
                }
                if ($index < count($chapters) - 1) {
                    $nextChapter = $chapters[$index + 1];
                }
                break;
            }
        }

        return $this->render('reader/read.html.twig', [
            'manga' => $manga,
            'chapters' => $chapters,
            'currentChapter' => $currentChapter,
            'prevChapter' => $prevChapter,
            'nextChapter' => $nextChapter,
        ]);
    }

    #[Route('/chapter/{id}/content', name: 'app_chapter_content')]
    public function getContent(Chapter $chapter): JsonResponse
    {
        // Vérifier si l'utilisateur est connecté
        if (!$this->getUser()) {
            return $this->json([
                'error' => 'Vous devez être connecté pour accéder au contenu.'
            ], 403);
        }

        return $this->json([
            'content' => $chapter->getContent(),
            'title' => $chapter->getTitle(),
            'number' => $chapter->getNumber(),
        ]);
    }
} 