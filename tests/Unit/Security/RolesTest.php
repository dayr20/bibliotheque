<?php

namespace App\Tests\Unit\Security;

use App\Security\Roles;
use PHPUnit\Framework\TestCase;

class RolesTest extends TestCase
{
    public function testGetAllRolesReturnsArray(): void
    {
        $roles = Roles::getAllRoles();

        $this->assertIsArray($roles);
        $this->assertNotEmpty($roles);
    }

    public function testAllRolesContainExpectedRoles(): void
    {
        $roles = Roles::getAllRoles();

        $this->assertArrayHasKey('ROLE_USER', $roles);
        $this->assertArrayHasKey('ROLE_ADMIN', $roles);
        $this->assertArrayHasKey('ROLE_SUPER_ADMIN', $roles);
    }

    public function testGetHighestRoleForAdmin(): void
    {
        $highest = Roles::getHighestRole(['ROLE_USER', 'ROLE_ADMIN']);

        $this->assertNotEmpty($highest);
    }

    public function testGetHighestRoleForUser(): void
    {
        $highest = Roles::getHighestRole(['ROLE_USER']);

        $this->assertNotEmpty($highest);
    }
}
