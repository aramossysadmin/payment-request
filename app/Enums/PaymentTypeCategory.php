<?php

namespace App\Enums;

enum PaymentTypeCategory: string
{
    case Payment = 'payment';
    case Investment = 'investment';

    public function label(): string
    {
        return match ($this) {
            self::Payment => 'Solicitud de Pago',
            self::Investment => 'Solicitud de Inversión',
        };
    }
}
