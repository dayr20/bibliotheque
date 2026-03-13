<?php

namespace App\Service;

use App\Entity\Manga;
use App\Entity\Genre;
use App\Repository\MangaRepository;
use App\Repository\GenreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MangaDexImporter
{
    public function __construct(
        private HttpClientInterface $client,
        private EntityManagerInterface $em,
        private MangaRepository $mangaRepository,
        private GenreRepository $genreRepository,
        private LoggerInterface $logger
    ) {}

    public function importPopularMangas(array $titles): int
    {
        $count = 0;
        $this->logger->info('Début de l\'importation des mangas populaires');

        // Vérifier que les genres existent
        $genres = $this->genreRepository->findAll();
        if (empty($genres)) {
            $this->logger->error('Aucun genre trouvé dans la base de données. Veuillez exécuter la commande app:init-database');
            return 0;
        }

        foreach ($titles as $mangaData) {
            try {
                $title = $mangaData['title'];
                $author = $mangaData['author'];
                
                $this->logger->info("Tentative d'importation de : $title");
                
                // Vérifier si le manga existe déjà
                if ($this->mangaRepository->findOneBy(['title' => $title])) {
                    $this->logger->info("Le manga '$title' existe déjà, ignoré.");
                    continue;
                }

                $response = $this->client->request('GET', 'https://api.mangadex.org/manga', [
                    'query' => [
                        'title' => $title,
                        'limit' => 1,
                        'includes[]' => ['cover_art', 'author'],
                        'contentRating[]' => ['safe', 'suggestive'],
                        'availableTranslatedLanguage[]' => ['en', 'fr']
                    ],
                ]);

                $data = $response->toArray();
                if (empty($data['data'])) {
                    $this->logger->warning("Aucun résultat trouvé pour '$title'");
                    continue;
                }

                $manga = $data['data'][0];
                $attrs = $manga['attributes'];
                
                // Vérifier le titre et l'auteur
                $foundTitle = $attrs['title']['en'] ?? $attrs['title']['fr'] ?? null;
                if (!$foundTitle) {
                    $this->logger->warning("Titre non trouvé pour '$title'");
                    continue;
                }

                // Récupérer la couverture
                $coverFileName = null;
                foreach ($manga['relationships'] as $rel) {
                    if ($rel['type'] === 'cover_art' && isset($rel['attributes']['fileName'])) {
                        $coverFileName = $rel['attributes']['fileName'];
                        break;
                    }
                }

                $imageUrl = $coverFileName
                    ? "https://uploads.mangadex.org/covers/{$manga['id']}/{$coverFileName}"
                    : null;

                // Créer le manga
                $mangaEntity = new Manga();
                $mangaEntity->setTitle($foundTitle);
                $mangaEntity->setAuthor($author);
                $mangaEntity->setCoverImage($imageUrl);
                $mangaEntity->setDescription($attrs['description']['en'] ?? $attrs['description']['fr'] ?? 'Pas de description disponible');
                $mangaEntity->setRating(mt_rand(8, 10));
                $mangaEntity->setIsNew(true);

                // Ajouter des genres aléatoires
                $randomGenres = array_rand(array_flip(['Action', 'Aventure', 'Fantasy', 'Drame']), 2);
                foreach ($randomGenres as $genreName) {
                    $genre = $this->genreRepository->findOneBy(['name' => $genreName]);
                    if ($genre) {
                        $mangaEntity->addGenre($genre);
                    }
                }

				$this->em->persist($mangaEntity);
                $count++;
                $this->logger->info("Manga '$foundTitle' importé avec succès");

            } catch (\Exception $e) {
                $this->logger->error("Erreur lors de l'importation de '$title': " . $e->getMessage());
            }
        }

        try {
            $this->em->flush();
            $this->logger->info("$count mangas ont été importés avec succès");
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de la sauvegarde des mangas: " . $e->getMessage());
            return 0;
        }

        return $count;
    }
}
