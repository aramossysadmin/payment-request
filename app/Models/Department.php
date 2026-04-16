<?php

namespace App\Models;

use Database\Factories\DepartmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    /** @use HasFactory<DepartmentFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'authorizer_level_1_id',
        'authorizer_level_2_id',
    ];

    protected function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = mb_strtoupper(trim($value));
    }

    protected function setDescriptionAttribute(?string $value): void
    {
        if ($value === null) {
            $this->attributes['description'] = null;

            return;
        }

        $trimmed = trim($value);
        $this->attributes['description'] = mb_strtoupper(mb_substr($trimmed, 0, 1)).mb_substr($trimmed, 1);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function authorizerLevel1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorizer_level_1_id');
    }

    public function authorizerLevel2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorizer_level_2_id');
    }

    public function investmentExpenseCategories(): HasMany
    {
        return $this->hasMany(InvestmentExpenseCategory::class);
    }
}
