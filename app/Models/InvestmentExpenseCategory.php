<?php

namespace App\Models;

use Database\Factories\InvestmentExpenseCategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvestmentExpenseCategory extends Model
{
    /** @use HasFactory<InvestmentExpenseCategoryFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'department_id',
        'is_active',
    ];

    protected function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = mb_strtoupper(trim($value));
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function investmentExpenseConcepts(): HasMany
    {
        return $this->hasMany(InvestmentExpenseConcept::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
