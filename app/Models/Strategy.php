<?php

namespace App\Models;

use App\Enums\IvaRate;
use Database\Factories\StrategyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Strategy extends Model
{
    /** @use HasFactory<StrategyFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * Campos que definen la matemática de la estrategia: no cambian una vez creada.
     *
     * @var array<int, string>
     */
    public const IMMUTABLE_ATTRIBUTES = ['name', 'rate', 'factor'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'rate', 'factor', 'is_active'])
            ->logOnlyDirty()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'rate',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $strategy) {
            $strategy->name = self::nameFromRate($strategy->getAttributes()['rate']);
        });

        static::updating(function (self $strategy) {
            if ($strategy->isDirty(self::IMMUTABLE_ATTRIBUTES)) {
                throw new LogicException('Los datos de una estrategia son inmutables una vez creada. Desactívala y crea una nueva.');
            }
        });

        static::forceDeleting(function () {
            throw new LogicException('Las estrategias no se eliminan definitivamente; solo pueden enviarse a la papelera.');
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate' => 'decimal:3',
            'factor' => 'decimal:6',
            'is_active' => 'boolean',
        ];
    }

    /**
     * El factor siempre se deriva de la tasa (4.5 → 0.045) para que nunca se desincronicen.
     */
    protected function setRateAttribute(string|int|float $value): void
    {
        $this->attributes['rate'] = $value;
        $this->attributes['factor'] = self::factorFromRate($value);
    }

    public static function factorFromRate(string|int|float $rate): string
    {
        return number_format(round((float) $rate / 100, 6), 6, '.', '');
    }

    /**
     * El nombre se genera desde la tasa para evitar errores de captura (4.50 → ESTRATEGIA 4.5).
     */
    public static function nameFromRate(string|int|float $rate): string
    {
        return 'ESTRATEGIA '.self::trimZeros(number_format((float) $rate, 3, '.', ''));
    }

    public static function trimZeros(string $value): string
    {
        return str_contains($value, '.') ? rtrim(rtrim($value, '0'), '.') : $value;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function investmentPaymentRequests(): HasMany
    {
        return $this->hasMany(InvestmentPaymentRequest::class);
    }

    /**
     * Calcula Total (con IVA), Subtotal e IVA a partir del Monto Estrategia.
     * Total = monto × (1 + factor); Subtotal = Total ÷ (1 + IVA); IVA = Total − Subtotal.
     * Se opera en centavos enteros para que Subtotal + IVA cuadre exacto con el Total.
     *
     * @return array{subtotal: string, iva: string, total: string}
     */
    public function amountsFor(string|int|float $strategyAmount, IvaRate $ivaRate): array
    {
        $amountCents = (int) round((float) $strategyAmount * 100);
        $factorMicros = (int) round((float) $this->factor * 1_000_000);
        $ivaHundredths = (int) round($ivaRate->rate() * 100);

        $totalCents = self::roundedDivision($amountCents * (1_000_000 + $factorMicros), 1_000_000);
        $subtotalCents = self::roundedDivision($totalCents * 100, 100 + $ivaHundredths);
        $ivaCents = $totalCents - $subtotalCents;

        return [
            'subtotal' => self::centsToString($subtotalCents),
            'iva' => self::centsToString($ivaCents),
            'total' => self::centsToString($totalCents),
        ];
    }

    private static function roundedDivision(int $numerator, int $denominator): int
    {
        return intdiv(2 * $numerator + $denominator, 2 * $denominator);
    }

    private static function centsToString(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
