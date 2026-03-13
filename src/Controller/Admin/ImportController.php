<?php

namespace App\Controller\Admin;

use App\Service\MangaDexImporter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/import')]
#[IsGranted('ROLE_ADMIN')]
class ImportController extends AbstractController
{
    private const POPULAR_MANGAS = [
        ['title' => 'DRAGON BALL', 'author' => 'Akira Toriyama'],
        ['title' => 'ONE PIECE', 'author' => 'Eiichiro Oda'],
        ['title' => 'NARUTO', 'author' => 'Masashi Kishimoto'],
        ['title' => 'Vagabond', 'author' => 'Takehiko Inoue'],
        ['title' => 'Monster', 'author' => 'Naoki Urasawa'],
        ['title' => 'Fullmetal Alchemist', 'author' => 'Hiromu Arakawa'],
        ['title' => 'JoJo no Kimyou na Bouken', 'author' => 'Hirohiko Araki'],
        ['title' => 'Astro Boy', 'author' => 'Osamu Tezuka'],
        ['title' => 'Slam Dunk', 'author' => 'Takehiko Inoue'],
        ['title' => 'Hunter x Hunter', 'author' => 'Yoshihiro Togashi'],
        ['title' => 'Bleach', 'author' => 'Tite Kubo'],
        ['title' => 'Jujutsu Kaisen', 'author' => 'Gege Akutami'],
        ['title' => 'Attack on Titan', 'author' => 'Hajime Isayama'],
        ['title' => 'Demon Slayer', 'author' => 'Koyoharu Gotouge'],
        ['title' => 'My Hero Academia', 'author' => 'Kohei Horikoshi'],
        ['title' => 'Chainsaw Man', 'author' => 'Tatsuki Fujimoto'],
        ['title' => 'Death Note', 'author' => 'Tsugumi Ohba'],
    ];

    #[Route('/mangas', name: 'admin_import_mangas', methods: ['GET'])]
    public function importMangas(MangaDexImporter $importer): Response
    {
        $count = $importer->importPopularMangas(self::POPULAR_MANGAS);

        if ($count > 0) {
            $this->addFlash('success', "$count mangas importés depuis MangaDex.");
        } else {
            $this->addFlash('warning', "Aucun manga n'a pu être importé depuis MangaDex.");
        }

        return $this->redirectToRoute('app_manga_index');
    }
}
