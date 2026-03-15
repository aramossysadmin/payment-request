<?php

namespace App\Models;

use Database\Factories\SocietyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Society extends Model
{
    /** @use HasFactory<SocietyFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
    ];

    protected function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = mb_strtoupper(trim($value));
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }
}
