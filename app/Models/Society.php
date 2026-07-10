<?php

namespace App\Models;

use Database\Factories\SocietyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Society extends Model
{
    /** @use HasFactory<SocietyFactory> */
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
        'rfc',
        'constancia_situacion_fiscal',
    ];

    protected function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = mb_strtoupper(trim($value));
    }

    protected function setRfcAttribute(?string $value): void
    {
        $this->attributes['rfc'] = $value === null ? null : mb_strtoupper(trim($value));
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }
}
