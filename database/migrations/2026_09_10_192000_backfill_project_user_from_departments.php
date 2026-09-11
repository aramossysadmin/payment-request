<?php

use App\Models\Department;
use App\Models\InvestmentRequest;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Asigna a cada usuario no privilegiado (sin rol super_admin, ceo ni project_manager)
     * los proyectos donde su(s) departamento(s) ya tengan al menos una Solicitud de
     * Inversión existente. Preserva el estado operativo actual: cada usuario mantiene
     * acceso a los proyectos donde ya opera. Los usuarios con rol privilegiado no
     * requieren asignación (tienen bypass total en el modelo).
     */
    public function up(): void
    {
        $privilegedRoles = ['super_admin', 'ceo', 'project_manager'];

        $nonPrivileged = User::query()
            ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', $privilegedRoles))
            ->get();

        foreach ($nonPrivileged as $user) {
            // Departamentos: pertenencia (pivot department_user) + autorización level 1/2.
            $memberDeptIds = $user->departments()->pluck('departments.id')->all();
            $authorizedDeptIds = Department::query()
                ->where('authorizer_level_1_id', $user->id)
                ->orWhere('authorizer_level_2_id', $user->id)
                ->pluck('id')
                ->all();
            $deptIds = array_unique(array_merge($memberDeptIds, $authorizedDeptIds));

            if (empty($deptIds)) {
                continue;
            }

            // Proyectos donde esos departamentos ya capturaron algún IR.
            $projectIds = InvestmentRequest::query()
                ->whereIn('department_id', $deptIds)
                ->whereNotNull('project_id')
                ->distinct()
                ->pluck('project_id')
                ->all();

            if (empty($projectIds)) {
                continue;
            }

            // syncWithoutDetaching = idempotente. Si la migración se re-ejecuta, no duplica.
            $user->projects()->syncWithoutDetaching($projectIds);
        }
    }

    /**
     * Reverse the migrations.
     *
     * No-op deliberado: la regla ABSOLUTA del proyecto prohíbe borrar registros
     * bajo cualquier circunstancia. Las filas creadas por este backfill pasaron
     * a ser datos regulares tan pronto como se hicieron persistentes. Si se
     * necesita revertir la feature completa, el rollback de la migración
     * `create_project_user_table` elimina la tabla entera junto con sus filas
     * (comportamiento nativo de `dropIfExists`), lo que es aceptable porque
     * afecta a la tabla como estructura, no a cada registro individualmente.
     */
    public function down(): void
    {
        // Intencionalmente vacío.
    }
};
