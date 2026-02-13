<?php

namespace App\Enums;

enum PaymentType: string
{
    case Full = 'full';
    case Advance = 'advance';

    public function label(): string
    {
        return match ($this) {
            self::Full => 'Completo',
            self::Advance => 'Anticipo',
        };
    }
}
