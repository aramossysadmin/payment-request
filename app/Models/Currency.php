<?php

namespace App\Models;

use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Currency extends Model
{
    /** @use HasFactory<CurrencyFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'name',
        'prefix',
        'exchange_rate',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'exchange_rate' => 'decimal:4',
        ];
    }

    protected function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = mb_strtoupper(trim($value));
    }

    protected function setPrefixAttribute(string $value): void
    {
        $this->attributes['prefix'] = mb_strtoupper(trim($value));
    }

    public function paymentRequests(): HasMany
    {
        return $this->hasMany(PaymentRequest::class);
    }
}
