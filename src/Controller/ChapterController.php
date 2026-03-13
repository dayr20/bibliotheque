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

class ChapterController extends AbstractController
{
    #[Route('/manga/{id}/chapters', name: 'app_manga_chapters')]
    public function index(Manga $manga): Response
    {
        return $this->render('chapter/index.html.twig', [
            'manga' => $manga,
            'chapters' => $manga->getChapters(),
        ]);
    }

    #[Route('/manga/{id}/chapter/new', name: 'app_chapter_new')]
    public function new(Request $request, Manga $manga, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        if ($request->isMethod('POST')) {
            $chapter = new Chapter();
            $chapter->setManga($manga);
            $chapter->setTitle($request->request->get('title'));
            $chapter->setNumber($request->request->get('number'));
            $chapter->setContent($request->request->get('content'));

            $entityManager->persist($chapter);
            $entityManager->flush();

            $this->addFlash('success', 'Le chapitre a été créé avec succès.');
            return $this->redirectToRoute('app_manga_chapters', ['id' => $manga->getId()]);
        }

        return $this->render('chapter/new.html.twig', [
            'manga' => $manga,
        ]);
    }

    #[Route('/chapter/{id}/edit', name: 'app_chapter_edit')]
    public function edit(Request $request, Chapter $chapter, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        if ($request->isMethod('POST')) {
            $chapter->setTitle($request->request->get('title'));
            $chapter->setNumber($request->request->get('number'));
            $chapter->setContent($request->request->get('content'));

            $entityManager->flush();

            $this->addFlash('success', 'Le chapitre a été modifié avec succès.');
            return $this->redirectToRoute('app_manga_chapters', ['id' => $chapter->getManga()->getId()]);
        }

        return $this->render('chapter/edit.html.twig', [
            'chapter' => $chapter,
        ]);
    }

    #[Route('/chapter/{id}/delete', name: 'app_chapter_delete', methods: ['POST'])]
    public function delete(Request $request, Chapter $chapter, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $mangaId = $chapter->getManga()->getId();
        $entityManager->remove($chapter);
        $entityManager->flush();

        $this->addFlash('success', 'Le chapitre a été supprimé avec succès.');
        return $this->redirectToRoute('app_manga_chapters', ['id' => $mangaId]);
    }

    #[Route('/chapter/{id}/content', name: 'app_chapter_content')]
    public function getContent(Chapter $chapter): JsonResponse
    {
        return $this->json([
            'content' => $chapter->getContent(),
            'title' => $chapter->getTitle(),
            'number' => $chapter->getNumber(),
        ]);
    }
} 