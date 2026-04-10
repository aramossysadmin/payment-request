<?php

namespace App\Models;

use App\Enums\DocumentMode;
use App\Enums\PaymentTypeCategory;
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
        'invoice_documents_mode',
        'additional_documents_mode',
        'is_active',
        'category',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'invoice_documents_mode' => DocumentMode::class,
            'additional_documents_mode' => DocumentMode::class,
            'is_active' => 'boolean',
            'category' => PaymentTypeCategory::class,
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

    public function scopeForPayments(Builder $query): Builder
    {
        return $query->where('category', PaymentTypeCategory::Payment);
    }

    public function scopeForInvestments(Builder $query): Builder
    {
        return $query->where('category', PaymentTypeCategory::Investment);
    }

    public function paymentRequests(): HasMany
    {
        return $this->hasMany(PaymentRequest::class);
    }

    public function investmentRequests(): HasMany
    {
        return $this->hasMany(InvestmentRequest::class);
    }
}
