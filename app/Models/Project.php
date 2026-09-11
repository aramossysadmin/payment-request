<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
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
        'branch_id',
        'currency_id',
        'start_date',
        'opening_date',
        'authorized_budget',
        'is_active',
        'closed_at',
        'requires_pm_approval',
    ];

    protected function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = mb_strtoupper(trim($value));
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'requires_pm_approval' => 'boolean',
            'start_date' => 'date',
            'opening_date' => 'date',
            'closed_at' => 'datetime',
            'authorized_budget' => 'decimal:2',
        ];
    }

    /**
     * Mantiene sincronizado `closed_at` con el toggle `is_active`:
     * al cerrar el proyecto (`is_active` pasa de true a false) se marca `closed_at = now()`;
     * al reabrirlo (`is_active` pasa de false a true) se limpia `closed_at = null`.
     * Se aplica en `saving` para cubrir cualquier vía de update (Filament, controllers, tinker).
     */
    protected static function booted(): void
    {
        static::saving(function (Project $project): void {
            if (! $project->isDirty('is_active')) {
                return;
            }

            $project->closed_at = $project->is_active ? null : now();
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function investmentRequests(): HasMany
    {
        return $this->hasMany(InvestmentRequest::class);
    }

    /**
     * Usuarios autorizados a acceder a este proyecto. La restricción no aplica a
     * usuarios con rol super_admin, ceo o project_manager (bypass total en
     * `User::hasElevatedAccess`).
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * Scope de proyectos activos (equivalente a "abiertos" en el nuevo modelo de estado):
     * `is_active = true` significa abierto para captura; `false` significa cerrado.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope que restringe la lista de proyectos a los accesibles por el usuario dado:
     * los roles privilegiados (super_admin, ceo, project_manager) ven todos; el
     * resto ve solo los proyectos que tienen asignados vía la pivote `project_user`.
     */
    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        if ($user->hasElevatedAccess()) {
            return $query;
        }

        return $query->whereIn('projects.id', $user->projects()->pluck('projects.id'));
    }
}
