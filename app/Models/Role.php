<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected static function booted(): void
    {
        static::addGlobalScope('hide_super_admin', function (Builder $query) {
            $user = auth()->user();

            if (! $user) {
                return;
            }

            $isSuperAdmin = DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('model_has_roles.model_id', $user->id)
                ->where('model_has_roles.model_type', $user::class)
                ->where('roles.name', 'super_admin')
                ->exists();

            if (! $isSuperAdmin) {
                $query->where('name', '!=', 'super_admin');
            }
        });
    }

    protected function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = Str::of($value)->trim()->lower()->replace(' ', '_')->toString();
    }
}
