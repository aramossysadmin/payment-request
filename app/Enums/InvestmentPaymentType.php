<?php

namespace App\Enums;

enum InvestmentPaymentType: string
{
    case Factura = 'factura';
    case Reembolso = 'reembolso';
    case Estrategia = 'estrategia';
    case Anticipo = 'anticipo';
    case Cotizacion = 'cotizacion';
    case Pagare = 'pagare';
    case Domiciliado = 'domiciliado';
    case FacturaEspana = 'factura_espana';

    public function label(): string
    {
        return match ($this) {
            self::Factura => 'Factura',
            self::Reembolso => 'Reembolso',
            self::Estrategia => 'Estrategia',
            self::Anticipo => 'Anticipo',
            self::Cotizacion => 'Cotización',
            self::Pagare => 'Pagaré',
            self::Domiciliado => 'Domiciliado',
            self::FacturaEspana => 'Factura España',
        };
    }

    public static function labelFor(?string $value): string
    {
        return self::tryFrom((string) $value)?->label() ?? '—';
    }
}
