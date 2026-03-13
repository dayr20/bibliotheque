<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Manga;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
    }

    public function testNewUserHasDefaultValues(): void
    {
        $this->assertNull($this->user->getId());
        $this->assertNull($this->user->getEmail());
        $this->assertFalse($this->user->isVerified());
        $this->assertNull($this->user->getVerificationCode());
        $this->assertNull($this->user->getVerificationCodeExpiresAt());
        $this->assertCount(0, $this->user->getFavoriteMangas());
    }

    public function testSetAndGetEmail(): void
    {
        $this->user->setEmail('test@example.com');
        $this->assertEquals('test@example.com', $this->user->getEmail());
        $this->assertEquals('test@example.com', $this->user->getUserIdentifier());
    }

    public function testRolesAlwaysContainRoleUser(): void
    {
        $this->assertContains('ROLE_USER', $this->user->getRoles());
    }

    public function testSetRolesKeepsRoleUser(): void
    {
        $this->user->setRoles(['ROLE_ADMIN']);
        $roles = $this->user->getRoles();

        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_ADMIN', $roles);
    }

    public function testSetRolesAreUnique(): void
    {
        $this->user->setRoles(['ROLE_USER', 'ROLE_USER', 'ROLE_ADMIN']);
        $roles = $this->user->getRoles();

        $this->assertCount(2, $roles);
    }

    public function testSetAndGetPassword(): void
    {
        $this->user->setPassword('hashed_password');
        $this->assertEquals('hashed_password', $this->user->getPassword());
    }

    public function testEraseCredentials(): void
    {
        $this->user->eraseCredentials();
        // Should not throw - just verifying the method exists
        $this->assertTrue(true);
    }

    public function testVerificationCode(): void
    {
        $this->user->setVerificationCode('abc123');
        $this->assertEquals('abc123', $this->user->getVerificationCode());

        $this->user->setVerificationCode(null);
        $this->assertNull($this->user->getVerificationCode());
    }

    public function testVerificationCodeExpiration(): void
    {
        // No expiration set = expired
        $this->assertTrue($this->user->isVerificationCodeExpired());

        // Future expiration = not expired
        $future = new \DateTimeImmutable('+30 minutes');
        $this->user->setVerificationCodeExpiresAt($future);
        $this->assertFalse($this->user->isVerificationCodeExpired());

        // Past expiration = expired
        $past = new \DateTimeImmutable('-1 minute');
        $this->user->setVerificationCodeExpiresAt($past);
        $this->assertTrue($this->user->isVerificationCodeExpired());
    }

    public function testIsVerified(): void
    {
        $this->assertFalse($this->user->isVerified());

        $this->user->setIsVerified(true);
        $this->assertTrue($this->user->isVerified());
    }

    public function testFavoriteMangas(): void
    {
        $manga = $this->createMock(Manga::class);

        // Add favorite
        $this->user->addFavoriteManga($manga);
        $this->assertCount(1, $this->user->getFavoriteMangas());
        $this->assertTrue($this->user->isMangaFavorite($manga));

        // Add same manga again - should not duplicate
        $this->user->addFavoriteManga($manga);
        $this->assertCount(1, $this->user->getFavoriteMangas());

        // Remove favorite
        $this->user->removeFavoriteManga($manga);
        $this->assertCount(0, $this->user->getFavoriteMangas());
        $this->assertFalse($this->user->isMangaFavorite($manga));
    }
}
