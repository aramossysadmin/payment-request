<?php

namespace App\Models;

use Database\Factories\InvestmentExpenseConceptFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvestmentExpenseConcept extends Model
{
    /** @use HasFactory<InvestmentExpenseConceptFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'investment_expense_category_id',
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(InvestmentExpenseCategory::class, 'investment_expense_category_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
