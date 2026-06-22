<?php

namespace App\Models;

use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Branch extends Model
{
    /** @use HasFactory<BranchFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logExcept(['sap_password']) // contraseña SAP NO se loguea por seguridad
            ->logOnlyDirty()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'name',
        'society_id',
        'sap_database',
        'sap_branch_id',
        'sap_user',
        'sap_password',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sap_password' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    protected function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = mb_strtoupper(trim($value));
    }

    public function society(): BelongsTo
    {
        return $this->belongsTo(Society::class);
    }

    public function paymentRequests(): HasMany
    {
        return $this->hasMany(PaymentRequest::class);
    }

    /**
     * True si la sucursal está activa Y tiene los 4 campos SAP configurados.
     * Usar como verificación previa antes de intentar conexión al Service Layer.
     */
    public function isSapConfigured(): bool
    {
        return (bool) $this->is_active
            && filled($this->sap_database)
            && filled($this->sap_branch_id)
            && filled($this->sap_user)
            && filled($this->sap_password);
    }
}
