<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminControllerTest extends WebTestCase
{
    public function testAdminDashboardRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/');

        $this->assertResponseRedirects('/login');
    }

    public function testAdminUsersRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/users');

        $this->assertResponseRedirects('/login');
    }

    public function testAdminImportRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/import/mangas');

        $this->assertResponseRedirects('/login');
    }
}
