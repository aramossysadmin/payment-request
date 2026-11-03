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
     * Crea el permiso de la casilla "Panel" y lo asigna a los roles existentes para
     * que nadie pierda el acceso al desplegar. Después se desmarca por rol en Shield.
     */
    public function up(): void
    {
        $permission = Permission::firstOrCreate(['name' => 'view_portal::dashboard', 'guard_name' => 'web']);

        Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', ['super_admin', 'admin_sp', 'administrador', 'project_manager', 'ceo'])
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
