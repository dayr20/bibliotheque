<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MangaControllerTest extends WebTestCase
{
    public function testMangaIndexIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/manga/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Liste des Mangas');
    }

    public function testMangaIndexContainsSearchForm(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/manga/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="title"]');
        $this->assertSelectorExists('input[name="author"]');
        $this->assertSelectorExists('select[name="genre"]');
    }

    public function testMangaSearchWithTitle(): void
    {
        $client = static::createClient();
        $client->request('GET', '/manga/?title=dragon');

        $this->assertResponseIsSuccessful();
    }

    public function testMangaSearchWithAuthor(): void
    {
        $client = static::createClient();
        $client->request('GET', '/manga/?author=toriyama');

        $this->assertResponseIsSuccessful();
    }

    public function testMangaNewRequiresAdmin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/manga/new');

        // Doit rediriger vers login (302) car pas authentifié
        $this->assertResponseRedirects();
    }

    public function testMangaShowNonExistentReturns404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/manga/99999');

        $this->assertResponseStatusCodeSame(404);
    }
}
