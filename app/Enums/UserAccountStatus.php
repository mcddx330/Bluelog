<?php

namespace App\Enums;

enum UserAccountStatus: string
{
    case Normal = 'normal';
    case EarlyAdopter = 'early_adopter';
    case Patron = 'patron';
    case EarlyAdopterAndPatron = 'early_adopter_and_patron';

    public function emoji(): string
    {
        return match ($this) {
            self::EarlyAdopter => '🎁',
            self::Patron => '✨',
            self::EarlyAdopterAndPatron => self::EarlyAdopter->emoji() . self::Patron->emoji(),
            default => '',
        };
    }
}
