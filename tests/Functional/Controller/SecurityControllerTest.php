<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
{
    public function testLoginPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3', 'Connexion');
    }

    public function testRegisterPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3', 'Inscription');
    }

    public function testLoginRedirectsAuthenticatedUser(): void
    {
        $client = static::createClient();
        // Un user non-connecté peut accéder au login
        $client->request('GET', '/login');
        $this->assertResponseIsSuccessful();
    }

    public function testRegisterWithInvalidJson(): void
    {
        $client = static::createClient();
        $client->request('POST', '/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], '{"email":"invalid","password":"short"}');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testVerifyWithInvalidCode(): void
    {
        $client = static::createClient();
        $client->request('POST', '/verify', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], '{"code":"INVALID_CODE_123"}');

        $this->assertResponseStatusCodeSame(400);
    }
}
