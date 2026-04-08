<?php

namespace App\Enums;

enum SystemRole: string
{
    case ADMIN = 'admin';
    case MANAGER = 'Manager';
    case OPERATIONS_MANAGER = 'operations_manager';
    case OPS_MANAGER_SPACED = 'Operations Manager';
    case ADMIN_CAP = 'Admin';

    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function managementRoles(): array
    {
        return [
            self::ADMIN->value,
            self::ADMIN_CAP->value,
            self::MANAGER->value,
            self::OPERATIONS_MANAGER->value,
            self::OPS_MANAGER_SPACED->value,
        ];
    }
}
