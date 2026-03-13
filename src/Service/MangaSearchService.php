<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class MangaSearchService
{
    private const MANGA_MAP = [
        'naruto' => [
            'id' => 'a1c7c817-4e59-43b7-9365-09675a149a6f',
            'author' => 'Masashi Kishimoto'
        ],
        'one piece' => [
            'id' => '6b1eb93e-473a-4ab3-9922-1a66d2a29a4a',
            'author' => 'Eiichiro Oda'
        ],
        'dragon ball' => [
            'id' => '24aa3880-8780-4fc6-b23e-49e6df1b3f7f',
            'author' => 'Akira Toriyama'
        ],
        'bleach' => [
            'id' => 'd8f6d3c6-a18b-4196-8d30-1052ed4e6c69',
            'author' => 'Tite Kubo'
        ],
        'jujutsu kaisen' => [
            'id' => '0650a331-c4d6-4f40-a767-5e4e6cb5bde4',
            'author' => 'Gege Akutami'
        ],
        'demon slayer' => [
            'id' => '789642f8-ca89-4e4e-8f7b-eee4d17ea08b',
            'author' => 'Koyoharu Gotouge'
        ],
        'attack on titan' => [
            'id' => '304ceac3-8cdb-4fe7-acf7-2b6ff7a60613',
            'author' => 'Hajime Isayama'
        ],
        'my hero academia' => [
            'id' => 'a96676e5-8ae2-425e-b549-7f15dd34a6d8',
            'author' => 'Kohei Horikoshi'
        ],
        'fullmetal alchemist' => [
            'id' => 'dd8a907a-3850-4f95-ba03-ba201a8399e3',
            'author' => 'Hiromu Arakawa'
        ],
        'death note' => [
            'id' => '75ee72ab-c6bf-4b87-badd-de839156934c',
            'author' => 'Tsugumi Ohba'
        ]
    ];

    public function __construct(
        private HttpClientInterface $client
    ) {}

    public function searchManga(string $query): array
    {
        try {
            // Normaliser la recherche (insensible à la casse et aux accents)
            $searchQuery = mb_strtolower(trim($query));
            $searchQuery = iconv('UTF-8', 'ASCII//TRANSLIT', $searchQuery);

            // Recherche dans notre map de mangas connus
            foreach (self::MANGA_MAP as $title => $info) {
                $normalizedTitle = mb_strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $title));
                if (str_contains($normalizedTitle, $searchQuery) || str_contains($searchQuery, $normalizedTitle)) {
                    $result = $this->fetchMangaById($info['id'], $info['author']);
                    if (!empty($result)) {
                        return $result;
                    }
                }
            }

            // Si non trouvé dans la map, faire une recherche sur l'API
            $response = $this->client->request('GET', 'https://api.mangadex.org/manga', [
                'query' => [
                    'title' => $query,
                    'limit' => 5,
                    'order[relevance]' => 'desc',
                    'includes' => ['cover_art', 'author'],
                    'contentRating' => ['safe', 'suggestive'],
                    'availableTranslatedLanguage' => ['en', 'fr']
                ]
            ]);

            $data = $response->toArray();
            
            if (empty($data['data'])) {
                return [];
            }

            $results = [];
            foreach ($data['data'] as $manga) {
                $title = $manga['attributes']['title']['en'] ?? $manga['attributes']['title']['fr'] ?? null;
                if (!$title) continue;

                // Récupérer l'auteur depuis les relations
                $author = 'Auteur inconnu';
                foreach ($manga['relationships'] as $rel) {
                    if ($rel['type'] === 'author' && isset($rel['attributes']['name'])) {
                        $author = $rel['attributes']['name'];
                        break;
                    }
                }

                // Récupérer la couverture depuis les relations
                $coverFileName = null;
                foreach ($manga['relationships'] as $rel) {
                    if ($rel['type'] === 'cover_art' && isset($rel['attributes']['fileName'])) {
                        $coverFileName = $rel['attributes']['fileName'];
                        break;
                    }
                }

                $results[] = [
                    'id' => $manga['id'],
                    'title' => $title,
                    'author' => $author,
                    'description' => $manga['attributes']['description']['en'] ?? $manga['attributes']['description']['fr'] ?? 'Pas de description disponible',
                    'coverImage' => $coverFileName ? "https://uploads.mangadex.org/covers/{$manga['id']}/$coverFileName" : null
                ];

                if (count($results) >= 5) break;
            }

            return $results;

        } catch (\Exception $e) {
            error_log("Erreur de recherche: " . $e->getMessage());
            return [];
        }
    }

    private function fetchMangaById(string $id, string $author): array
    {
        try {
            $response = $this->client->request('GET', "https://api.mangadex.org/manga/$id", [
                'query' => [
                    'includes[]' => ['cover_art', 'author']
                ]
            ]);

            $data = $response->toArray();

            if (empty($data['data'])) {
                error_log("Manga non trouvé avec l'ID: $id");
                return [];
            }

            $manga = $data['data'];
            $attrs = $manga['attributes'];

            // Récupérer la couverture depuis les relations
            $coverFileName = null;
            foreach ($manga['relationships'] as $rel) {
                if ($rel['type'] === 'cover_art' && isset($rel['attributes']['fileName'])) {
                    $coverFileName = $rel['attributes']['fileName'];
                    break;
                }
            }

            // Vérifier si l'auteur dans les relations correspond à celui attendu
            $foundAuthor = false;
            foreach ($manga['relationships'] as $rel) {
                if ($rel['type'] === 'author' && isset($rel['attributes']['name'])) {
                    if (strtolower($rel['attributes']['name']) === strtolower($author)) {
                        $foundAuthor = true;
                        break;
                    }
                }
            }

            if (!$foundAuthor) {
                error_log("L'auteur trouvé ne correspond pas à l'auteur attendu pour le manga: $id");
                return [];
            }

            return [[
                'id' => $manga['id'],
                'title' => $attrs['title']['en'] ?? $attrs['title']['fr'] ?? 'Titre inconnu',
                'author' => $author,
                'description' => $attrs['description']['en'] ?? $attrs['description']['fr'] ?? 'Pas de description disponible',
                'coverImage' => $coverFileName ? "https://uploads.mangadex.org/covers/$id/$coverFileName" : null
            ]];
        } catch (\Exception $e) {
            error_log("Erreur lors de la récupération du manga $id: " . $e->getMessage());
            return [];
        }
    }

    private function getCoverUrl(string $mangaId): string
    {
        return "https://uploads.mangadex.org/covers/$mangaId/cover.jpg";
    }

    public function getMangaDetails(string $id): ?array
    {
        try {
            $response = $this->client->request('GET', "https://api.mangadex.org/manga/$id", [
                'query' => [
                    'includes[]' => ['cover_art', 'author', 'artist']
                ]
            ]);

            $data = $response->toArray();

            if (empty($data['data'])) {
                return null;
            }

            $manga = $data['data'];
            $attrs = $manga['attributes'];

            // Récupérer la couverture
            $coverFileName = null;
            $author = 'Auteur inconnu';
            $artist = 'Artiste inconnu';

            foreach ($manga['relationships'] as $rel) {
                if ($rel['type'] === 'cover_art' && isset($rel['attributes']['fileName'])) {
                    $coverFileName = $rel['attributes']['fileName'];
                }
                if ($rel['type'] === 'author' && isset($rel['attributes']['name'])) {
                    $author = $rel['attributes']['name'];
                }
                if ($rel['type'] === 'artist' && isset($rel['attributes']['name'])) {
                    $artist = $rel['attributes']['name'];
                }
            }

            // Récupérer les tags/genres
            $tags = [];
            foreach ($attrs['tags'] as $tag) {
                $tagName = $tag['attributes']['name']['en'] ?? $tag['attributes']['name']['fr'] ?? null;
                if ($tagName) {
                    $tags[] = $tagName;
                }
            }

            return [
                'id' => $manga['id'],
                'title' => $attrs['title']['en'] ?? $attrs['title']['fr'] ?? 'Titre inconnu',
                'author' => $author,
                'artist' => $artist,
                'description' => $attrs['description']['en'] ?? $attrs['description']['fr'] ?? 'Pas de description disponible',
                'coverImage' => $coverFileName ? "https://uploads.mangadex.org/covers/$id/$coverFileName" : null,
                'status' => $attrs['status'] ?? 'unknown',
                'tags' => $tags,
                'year' => $attrs['year'] ?? null,
                'contentRating' => $attrs['contentRating'] ?? 'safe'
            ];

        } catch (\Exception $e) {
            error_log("Erreur lors de la récupération des détails du manga $id: " . $e->getMessage());
            return null;
        }
    }
} 