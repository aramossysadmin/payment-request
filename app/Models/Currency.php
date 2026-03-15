<?php

namespace App\Models;

use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Currency extends Model
{
    /** @use HasFactory<CurrencyFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'prefix',
    ];

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
