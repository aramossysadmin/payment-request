<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, LogsActivity, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logExcept(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at'])
            ->logOnlyDirty()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->dontSubmitEmptyLogs();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'department_id',
        'position_id',
    ];

    protected function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = mb_convert_case(trim($value), MB_CASE_TITLE, 'UTF-8');
    }

    protected function setEmailAttribute(string $value): void
    {
        $this->attributes['email'] = mb_strtolower(trim($value));
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Todos los departamentos a los que pertenece el usuario (principal + adicionales).
     * La pivot `department_user` contiene tanto el principal (por backfill inicial)
     * como los adicionales (asignados desde el admin Filament).
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_user')->withTimestamps();
    }

    /**
     * True si el usuario pertenece al departamento dado (principal o adicional).
     */
    public function belongsToDepartment(int $departmentId): bool
    {
        return $this->departments()->where('departments.id', $departmentId)->exists();
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function paymentRequests(): HasMany
    {
        return $this->hasMany(PaymentRequest::class);
    }

    /**
     * @return Builder<Department>
     */
    public function authorizedDepartments(): Builder
    {
        return Department::where('authorizer_level_1_id', $this->id)
            ->orWhere('authorizer_level_2_id', $this->id);
    }

    /**
     * Proyectos a los que este usuario tiene acceso explícito.
     * Los usuarios con rol super_admin, ceo o project_manager tienen acceso total
     * automáticamente y esta relación no aplica para ellos (ver hasElevatedAccess).
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)->withTimestamps();
    }

    /**
     * True si el usuario tiene bypass automático sobre la restricción de acceso
     * a proyectos (ve todos sin necesidad de asignación explícita).
     */
    public function hasElevatedAccess(): bool
    {
        return $this->hasAnyRole(['super_admin', 'ceo', 'project_manager']);
    }

    /**
     * True si el usuario puede acceder al proyecto dado. Los roles privilegiados
     * tienen bypass total; el resto necesita tener el proyecto en su lista
     * de proyectos autorizados. Un proyecto `null` (para IRs históricos sin
     * project_id) se considera accesible.
     */
    public function canAccessProject(?Project $project): bool
    {
        if ($project === null) {
            return true;
        }

        if ($this->hasElevatedAccess()) {
            return true;
        }

        return $this->projects()->where('projects.id', $project->id)->exists();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
