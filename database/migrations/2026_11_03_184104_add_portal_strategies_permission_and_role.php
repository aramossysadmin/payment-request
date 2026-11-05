<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Crea el permiso del recurso "Estrategias" y el rol `estrategias`. También marca
     * la casilla en `super_admin`; después se puede desmarcar por rol en Shield.
     */
    public function up(): void
    {
        $permission = Permission::firstOrCreate(['name' => 'view_portal::strategies', 'guard_name' => 'web']);

        Role::firstOrCreate(['name' => 'estrategias', 'guard_name' => 'web'])->givePermissionTo($permission);

        Role::query()
            ->where('guard_name', 'web')
            ->where('name', 'super_admin')
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($permission));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     *
     * No-op deliberado: la regla del proyecto prohíbe borrar registros.
     */
    public function down(): void
    {
        // Intencionalmente vacío.
    }
};
