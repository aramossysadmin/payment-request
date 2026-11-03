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
    case NominaFiscal = 'nomina_fiscal';

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
            self::NominaFiscal => 'Nómina Fiscal',
        };
    }

    public static function labelFor(?string $value): string
    {
        return self::tryFrom((string) $value)?->label() ?? '—';
    }

    /**
     * Extensiones del documento único de los tipos que no son `factura`.
     *
     * @return list<string>
     */
    public static function singleDocumentExtensionsFor(mixed $value): array
    {
        $extensions = ['pdf', 'jpg', 'jpeg', 'png'];

        return $value === self::NominaFiscal->value
            ? [...$extensions, 'xls', 'xlsx']
            : $extensions;
    }

    public static function singleDocumentFormatMessageFor(mixed $value): string
    {
        return $value === self::NominaFiscal->value
            ? 'El documento debe ser PDF, imagen (JPG, JPEG o PNG) o Excel (XLS o XLSX).'
            : 'El documento debe ser PDF o imagen (JPG, JPEG o PNG).';
    }
}
