<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class MangaDexService
{
    private const API_BASE_URL = 'https://api.mangadex.org';
    private const COVER_BASE_URL = 'https://uploads.mangadex.org/covers';

    public function __construct(
        private HttpClientInterface $httpClient
    ) {}

    public function getManga(string $id): ?array
    {
        try {
            $response = $this->httpClient->request('GET', self::API_BASE_URL . '/manga/' . $id, [
                'query' => [
                    'includes[]' => ['cover_art', 'author', 'artist', 'manga'],
                    'contentRating[]' => ['safe', 'suggestive']
                ]
            ]);

            $data = $response->toArray();
            if (!isset($data['data'])) {
                return null;
            }

            return $this->formatMangaData($data['data']);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function searchMangas(string $query = '', int $limit = 10, int $offset = 0): array
    {
        try {
            $response = $this->httpClient->request('GET', self::API_BASE_URL . '/manga', [
                'query' => [
                    'title' => $query,
                    'limit' => $limit,
                    'offset' => $offset,
                    'includes[]' => ['cover_art', 'author', 'artist', 'manga'],
                    'contentRating[]' => ['safe', 'suggestive'],
                    'order[relevance]' => 'desc'
                ]
            ]);

            $data = $response->toArray();
            if (!isset($data['data'])) {
                return [];
            }

            return array_map([$this, 'formatMangaData'], $data['data']);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getPopularMangas(int $limit = 10): array
    {
        try {
            $response = $this->httpClient->request('GET', self::API_BASE_URL . '/manga', [
                'query' => [
                    'limit' => $limit,
                    'includes[]' => ['cover_art', 'author', 'artist', 'manga'],
                    'contentRating[]' => ['safe', 'suggestive'],
                    'order[rating]' => 'desc',
                    'hasAvailableChapters' => true
                ]
            ]);

            $data = $response->toArray();
            if (!isset($data['data'])) {
                return [];
            }

            return array_map([$this, 'formatMangaData'], $data['data']);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getLatestMangas(int $limit = 10): array
    {
        try {
            $response = $this->httpClient->request('GET', self::API_BASE_URL . '/manga', [
                'query' => [
                    'limit' => $limit,
                    'includes[]' => ['cover_art', 'author', 'artist', 'manga'],
                    'contentRating[]' => ['safe', 'suggestive'],
                    'order[createdAt]' => 'desc',
                    'hasAvailableChapters' => true
                ]
            ]);

            $data = $response->toArray();
            if (!isset($data['data'])) {
                return [];
            }

            return array_map([$this, 'formatMangaData'], $data['data']);
        } catch (\Exception $e) {
            return [];
        }
    }

    private function formatMangaData(array $mangaData): array
    {
        $attributes = $mangaData['attributes'];
        $relationships = $mangaData['relationships'];

        // Trouver la couverture et son filename directement dans les attributs grâce à l'expansion
        $coverUrl = null;
        foreach ($relationships as $rel) {
            if ($rel['type'] === 'cover_art' && isset($rel['attributes']['fileName'])) {
                $coverUrl = sprintf('%s/%s/%s', 
                    self::COVER_BASE_URL, 
                    $mangaData['id'], 
                    $rel['attributes']['fileName']
                );
                break;
            }
        }

        // Trouver l'auteur directement dans les attributs grâce à l'expansion
        $author = null;
        foreach ($relationships as $rel) {
            if ($rel['type'] === 'author' && isset($rel['attributes']['name'])) {
                $author = $rel['attributes']['name'];
                break;
            }
        }

        // Obtenir le titre dans la langue préférée
        $title = $attributes['title']['fr'] ?? 
                 $attributes['title']['en'] ?? 
                 $attributes['title']['ja-ro'] ?? 
                 $attributes['title']['ja'] ?? 
                 array_values($attributes['title'])[0] ?? 
                 'Sans titre';

        // Obtenir la description dans la langue préférée
        $description = $attributes['description']['fr'] ?? 
                      $attributes['description']['en'] ?? 
                      array_values($attributes['description'])[0] ?? 
                      'Pas de description disponible';

        return [
            'id' => $mangaData['id'],
            'title' => $title,
            'author' => $author,
            'description' => $description,
            'coverImage' => $coverUrl,
            'status' => $attributes['status'] ?? 'unknown',
            'year' => $attributes['year'],
            'tags' => array_map(function($tag) {
                return $tag['attributes']['name']['fr'] ?? 
                       $tag['attributes']['name']['en'] ?? 
                       array_values($tag['attributes']['name'])[0];
            }, $attributes['tags'] ?? []),
            'isNew' => (new \DateTime($attributes['createdAt']))->diff(new \DateTime())->days < 30,
            'rating' => $attributes['rating'] ?? 0
        ];
    }
} 