<?php

namespace App\Enums;

enum InvestmentPaymentType: string
{
    case Factura = 'factura';
    case Reembolso = 'reembolso';
    case Estrategia = 'estrategia';
    case Anticipo = 'anticipo';

    public function label(): string
    {
        return match ($this) {
            self::Factura => 'Factura',
            self::Reembolso => 'Reembolso',
            self::Estrategia => 'Estrategia',
            self::Anticipo => 'Anticipo',
        };
    }

    public static function labelFor(?string $value): string
    {
        return self::tryFrom((string) $value)?->label() ?? '—';
    }
}
