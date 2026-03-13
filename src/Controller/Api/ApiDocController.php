<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiDocController extends AbstractController
{
    #[Route('/api/doc', name: 'api_doc')]
    public function index(): Response
    {
        return $this->render('api/doc.html.twig');
    }

    #[Route('/api/v1/openapi.json', name: 'api_openapi_json', methods: ['GET'])]
    public function openApiJson(): Response
    {
        $spec = [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'MangaZone API',
                'description' => 'API REST pour la bibliothèque manga MangaZone',
                'version' => '1.0.0',
                'contact' => ['email' => 'contact@mangazone.fr'],
            ],
            'servers' => [
                ['url' => '/api/v1', 'description' => 'Serveur de développement'],
            ],
            'paths' => [
                '/mangas' => [
                    'get' => [
                        'summary' => 'Liste des mangas',
                        'description' => 'Récupère la liste paginée des mangas avec filtres optionnels.',
                        'operationId' => 'listMangas',
                        'tags' => ['Mangas'],
                        'parameters' => [
                            ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 1], 'description' => 'Numéro de page'],
                            ['name' => 'limit', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 20, 'maximum' => 50], 'description' => 'Nombre de résultats par page (max 50)'],
                            ['name' => 'title', 'in' => 'query', 'schema' => ['type' => 'string'], 'description' => 'Filtrer par titre (recherche partielle)'],
                            ['name' => 'author', 'in' => 'query', 'schema' => ['type' => 'string'], 'description' => 'Filtrer par auteur (recherche partielle)'],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Liste des mangas',
                                'content' => ['application/json' => ['schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Manga']],
                                        'meta' => ['$ref' => '#/components/schemas/Pagination'],
                                    ],
                                ]]],
                            ],
                        ],
                    ],
                ],
                '/mangas/{id}' => [
                    'get' => [
                        'summary' => 'Détails d\'un manga',
                        'description' => 'Récupère les détails complets d\'un manga avec ses chapitres.',
                        'operationId' => 'showManga',
                        'tags' => ['Mangas'],
                        'parameters' => [
                            ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer'], 'description' => 'ID du manga'],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Détails du manga',
                                'content' => ['application/json' => ['schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'data' => ['$ref' => '#/components/schemas/MangaDetail'],
                                    ],
                                ]]],
                            ],
                            '404' => ['description' => 'Manga non trouvé'],
                        ],
                    ],
                ],
                '/mangas/popular' => [
                    'get' => [
                        'summary' => 'Mangas populaires',
                        'description' => 'Récupère les mangas les mieux notés.',
                        'operationId' => 'popularMangas',
                        'tags' => ['Mangas'],
                        'parameters' => [
                            ['name' => 'limit', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 10, 'maximum' => 50], 'description' => 'Nombre de résultats'],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Liste des mangas populaires',
                                'content' => ['application/json' => ['schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Manga']],
                                    ],
                                ]]],
                            ],
                        ],
                    ],
                ],
            ],
            'components' => [
                'schemas' => [
                    'Manga' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'title' => ['type' => 'string'],
                            'author' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'cover_image' => ['type' => 'string', 'nullable' => true],
                            'rating' => ['type' => 'number', 'format' => 'float'],
                            'is_new' => ['type' => 'boolean'],
                            'status' => ['type' => 'string', 'nullable' => true],
                            'year' => ['type' => 'integer', 'nullable' => true],
                            'genres' => ['type' => 'array', 'items' => ['type' => 'string']],
                        ],
                    ],
                    'MangaDetail' => [
                        'allOf' => [
                            ['$ref' => '#/components/schemas/Manga'],
                            ['type' => 'object', 'properties' => [
                                'chapters' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Chapter']],
                            ]],
                        ],
                    ],
                    'Chapter' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'number' => ['type' => 'integer'],
                            'title' => ['type' => 'string'],
                            'created_at' => ['type' => 'string', 'format' => 'date-time'],
                        ],
                    ],
                    'Pagination' => [
                        'type' => 'object',
                        'properties' => [
                            'page' => ['type' => 'integer'],
                            'limit' => ['type' => 'integer'],
                            'total' => ['type' => 'integer'],
                            'pages' => ['type' => 'integer'],
                        ],
                    ],
                ],
            ],
        ];

        return $this->json($spec);
    }
}
