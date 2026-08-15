<?php

namespace App\Enums;

enum ProductStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function isArchived(): bool
    {
        return $this === self::Archived;
    }
}
