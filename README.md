# MangaZone - Bibliotheque Manga

Application web de gestion de bibliotheque manga construite avec **Symfony 6.4 LTS**.

Recherchez, organisez et suivez vos mangas preferes avec un systeme complet de listes de lecture, d'avis, de statistiques et une API REST.

![Symfony](https://img.shields.io/badge/Symfony-6.4-purple?logo=symfony)
![PHP](https://img.shields.io/badge/PHP-8.2+-blue?logo=php)
![MySQL](https://img.shields.io/badge/MySQL-8.0-orange?logo=mysql)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-purple?logo=bootstrap)
![Docker](https://img.shields.io/badge/Docker-Compose-blue?logo=docker)
![License](https://img.shields.io/badge/License-Proprietary-red)

---

## Fonctionnalites

### Gestion des Mangas
- Catalogue complet avec couvertures, descriptions, genres et notes
- Recherche avancee avec filtres (titre, auteur, genre, statut, annee, note minimum)
- Tri par note, titre, annee ou nouveautes
- Import de mangas depuis l'API MangaDex
- Gestion des chapitres par manga

### Systeme Utilisateur
- Inscription avec validation email et code de verification (expire en 30 min)
- Politique de mot de passe forte (majuscule, minuscule, chiffre, caractere special)
- Roles : `ROLE_USER`, `ROLE_ADMIN`, `ROLE_SUPER_ADMIN`
- Upload d'avatar et nom d'affichage personnalise
- Page parametres profil

### Listes de Lecture
- 4 statuts : A lire, En cours, Termine, Abandonne
- Historique de lecture avec suivi du dernier chapitre lu
- Gestion des favoris

### Systeme d'Avis
- Notes de 1 a 5 etoiles avec commentaires
- Modification et suppression de ses propres avis
- Note moyenne des lecteurs affichee sur chaque manga
- Protection CSRF sur toutes les actions

### Notifications
- Centre de notifications avec badge temps reel (polling 30s)
- Types : nouveau chapitre, mise a jour manga, systeme
- Marquage lu/non-lu individuel ou global

### Statistiques Utilisateur
- Graphiques interactifs avec Chart.js
- Repartition des lectures (donut chart)
- Distribution des notes donnees (bar chart)
- Genres preferes (horizontal bar chart)
- Chiffres cles : mangas lus, avis donnes, favoris

### API REST
- `GET /api/v1/mangas` - Liste paginee avec filtres
- `GET /api/v1/mangas/{id}` - Details avec chapitres
- `GET /api/v1/mangas/popular` - Mangas populaires
- `GET /api/v1/mangas/search` - Recherche avancee avec pagination
- Documentation Swagger UI sur `/api/doc`
- Specification OpenAPI 3.0 sur `/api/v1/openapi.json`

### Progressive Web App (PWA)
- Installable sur mobile et desktop
- Mode hors ligne avec page dediee
- Service Worker avec strategie network-first
- Cache automatique des pages visitees

### Securite
- APP_SECRET genere de maniere securisee
- Rate limiting sur login (5/5min), inscription (3/10min), verification (5/5min)
- SRI (Subresource Integrity) sur les CDN Bootstrap et Font Awesome
- Protection CSRF sur tous les formulaires
- Cookies remember_me securises (httponly, samesite strict)
- Blocage de connexion pour les comptes non verifies
- Validation email avec `filter_var`

---

## Stack Technique

| Composant | Technologie |
|-----------|-------------|
| **Backend** | PHP 8.2+, Symfony 6.4 LTS |
| **Base de donnees** | MySQL 8.0, Doctrine ORM |
| **Frontend** | Twig, Bootstrap 5.3, Font Awesome 6 |
| **Assets** | Webpack Encore |
| **Graphiques** | Chart.js 4 |
| **API Doc** | Swagger UI, OpenAPI 3.0 |
| **Tests** | PHPUnit 9 |
| **CI/CD** | GitLab CI (lint, test, build) |
| **Conteneurs** | Docker Compose |
| **API externe** | MangaDex API |

---

## Installation

### Prerequis
- Docker et Docker Compose
- Node.js 18+ et npm

### Lancement rapide

```bash
# 1. Cloner le projet
git clone https://github.com/dayr20/bibliotheque.git
cd bibliotheque

# 2. Copier le fichier d'environnement
cp .env.example .env

# 3. Lancer les conteneurs Docker
docker compose up -d

# 4. Installer les dependances PHP
docker compose exec php composer install

# 5. Installer les dependances JS et compiler les assets
npm install
npx encore dev

# 6. Creer la base de donnees et executer les migrations
docker compose exec php php bin/console doctrine:database:create --if-not-exists
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction

# 7. Acceder a l'application
# http://localhost:8000
```

### Services disponibles

| Service | URL |
|---------|-----|
| Application | http://localhost:8000 |
| PHPMyAdmin | http://localhost:8080 |
| MailHog | http://localhost:8026 |
| Mongo Express | http://localhost:8081 |
| API Documentation | http://localhost:8000/api/doc |

---

## Structure du Projet

```
bibliotheque/
├── assets/                  # JS et CSS (Webpack Encore)
│   ├── app.js              # Point d'entree JS (PWA, AJAX, notifications)
│   └── styles/app.css      # Theme dark manga complet
├── config/                  # Configuration Symfony
├── docker/                  # Fichiers Docker
│   ├── Dockerfile.prod     # Image production multi-stage (Nginx + PHP-FPM)
│   ├── nginx-prod.conf     # Configuration Nginx optimisee
│   ├── opcache-prod.ini    # OPcache production
│   └── supervisord.conf    # Supervisor pour Nginx + PHP-FPM
├── migrations/              # Migrations Doctrine
├── public/                  # Fichiers publics
│   ├── manifest.json       # PWA manifest
│   ├── sw.js               # Service Worker
│   └── icons/              # Icones PWA
├── src/
│   ├── Controller/
│   │   ├── Admin/          # Dashboard, Users, Import
│   │   ├── Api/            # REST API + Swagger Doc
│   │   ├── MangaController.php
│   │   ├── ProfileController.php
│   │   ├── NotificationController.php
│   │   └── StatsController.php
│   ├── Entity/
│   │   ├── User.php        # Utilisateur (avatar, roles, favoris)
│   │   ├── Manga.php       # Manga (titre, auteur, genres, chapitres)
│   │   ├── Review.php      # Avis (note 1-5, commentaire)
│   │   ├── ReadingList.php # Liste de lecture (statuts)
│   │   ├── ReadingProgress.php  # Progression de lecture
│   │   └── Notification.php     # Notifications
│   ├── Repository/         # Repositories Doctrine
│   ├── Service/            # Services metier
│   ├── Security/           # Authenticator, Roles
│   └── EventListener/      # Rate Limiting
├── templates/               # Templates Twig
├── tests/
│   ├── Unit/               # Tests unitaires
│   └── Functional/         # Tests fonctionnels
├── .gitlab-ci.yml          # Pipeline CI/CD
├── docker-compose.yml      # Dev environment
└── docker-compose.prod.yml # Production environment
```

---

## Tests

```bash
# Lancer tous les tests
docker compose exec php php bin/phpunit

# Tests unitaires uniquement
docker compose exec php php bin/phpunit tests/Unit/

# Tests fonctionnels uniquement
docker compose exec php php bin/phpunit tests/Functional/
```

### Couverture des tests
- **Unit/Entity/UserTest** - Valeurs par defaut, roles, favoris, expiration verification
- **Unit/Service/AuthServiceTest** - Inscription, validation, verification, codes expires
- **Unit/Security/RolesTest** - Constantes de roles
- **Functional/Controller/SecurityControllerTest** - Pages login/register, requetes invalides
- **Functional/Controller/HomeControllerTest** - Page d'accueil
- **Functional/Controller/MangaControllerTest** - Liste, recherche, protection admin
- **Functional/Controller/AdminControllerTest** - Routes admin protegees

---

## Deploiement Production

```bash
# Construire et lancer en production
docker compose -f docker-compose.prod.yml up -d --build

# Variables d'environnement requises
APP_SECRET=<votre-secret>
DB_PASSWORD=<mot-de-passe-db>
DB_ROOT_PASSWORD=<mot-de-passe-root>
```

L'image de production utilise un build multi-stage :
1. **Stage Composer** - Installation des dependances PHP (--no-dev)
2. **Stage Node** - Compilation des assets (encore production)
3. **Stage Production** - Nginx + PHP-FPM avec OPcache optimise

---

## API Endpoints

### Mangas

```
GET  /api/v1/mangas              # Liste paginee
GET  /api/v1/mangas/{id}         # Details + chapitres
GET  /api/v1/mangas/popular      # Mangas populaires
GET  /api/v1/mangas/search       # Recherche avancee
```

### Parametres de recherche

| Parametre | Type | Description |
|-----------|------|-------------|
| `page` | int | Numero de page (defaut: 1) |
| `limit` | int | Resultats par page (defaut: 20, max: 50) |
| `title` | string | Filtrer par titre |
| `author` | string | Filtrer par auteur |
| `status` | string | Filtrer par statut (ongoing, completed, hiatus) |
| `year` | int | Filtrer par annee |
| `rating_min` | float | Note minimum |
| `sort` | string | Tri : rating, title, year, newest |

### Exemple de reponse

```json
{
  "data": [
    {
      "id": 1,
      "title": "One Piece",
      "author": "Eiichiro Oda",
      "description": "...",
      "cover_image": "https://...",
      "rating": 4.9,
      "is_new": false,
      "status": "ongoing",
      "year": 1997,
      "genres": ["Action", "Adventure", "Fantasy"]
    }
  ],
  "meta": {
    "page": 1,
    "limit": 20,
    "total": 150,
    "pages": 8
  }
}
```

---

## Auteur

Projet realise dans le cadre d'un portfolio de developpement web.

---

*Built with Symfony 6.4 LTS*
