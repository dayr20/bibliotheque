<?php

namespace App\Security;

class Roles
{
    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    public static function getAllRoles(): array
    {
        return [
            'Utilisateur' => self::ROLE_USER,
            'Administrateur' => self::ROLE_ADMIN,
            'Super Administrateur' => self::ROLE_SUPER_ADMIN,
        ];
    }

    public static function getHighestRole(array $roles): string
    {
        if (in_array(self::ROLE_SUPER_ADMIN, $roles)) {
            return 'Super Administrateur';
        }
        if (in_array(self::ROLE_ADMIN, $roles)) {
            return 'Administrateur';
        }
        return 'Utilisateur';
    }
} 