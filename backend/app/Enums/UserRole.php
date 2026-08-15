<?php

namespace App\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case Admin = 'admin';
    case Manager = 'manager';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Customer',
            self::Admin => 'Administrator',
            self::Manager => 'Manager',
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    public function isManager(): bool
    {
        return $this === self::Manager;
    }

    public function canAccessAdmin(): bool
    {
        return in_array($this, [self::Admin, self::Manager], true);
    }
}
