<?php

namespace App\Enums;

enum PaymentType: string
{
    case Invoice = 'invoice';
    case Advance = 'advance';
    case Investment = 'investment';

    public function label(): string
    {
        return match ($this) {
            self::Invoice => 'Pago con Factura',
            self::Advance => 'Anticipo',
            self::Investment => 'Inversiones',
        };
    }
}
