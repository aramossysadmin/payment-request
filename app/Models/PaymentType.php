<?php

namespace App\Models;

use Database\Factories\PaymentTypeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PaymentType extends Model
{
    /** @use HasFactory<PaymentTypeFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'requires_invoice_documents',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requires_invoice_documents' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = mb_strtoupper(trim($value));
    }

    protected static function booted(): void
    {
        static::creating(function (PaymentType $paymentType) {
            if (! $paymentType->slug) {
                $paymentType->slug = Str::slug($paymentType->name);
            }
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function paymentRequests(): HasMany
    {
        return $this->hasMany(PaymentRequest::class);
    }
}
