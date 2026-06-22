<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfill de la pivot `department_user` desde el `users.department_id` actual.
     *
     * Después de esta migración, cada usuario con `department_id != null` tendrá
     * al menos una entrada en `department_user` apuntando a su dpto principal.
     *
     * Es idempotente: la unique constraint (department_id, user_id) de la pivot
     * previene duplicados si se ejecuta más de una vez.
     */
    public function up(): void
    {
        $rows = DB::table('users')
            ->whereNotNull('department_id')
            ->select('id as user_id', 'department_id')
            ->get();

        if ($rows->isEmpty()) {
            return;
        }

        foreach ($rows as $row) {
            DB::table('department_user')->updateOrInsert(
                [
                    'user_id' => $row->user_id,
                    'department_id' => $row->department_id,
                ],
                []
            );
        }
    }

    /**
     * Down: borra solo las entradas que coincidan EXACTAMENTE con el
     * users.department_id actual. Esto preserva los dptos adicionales
     * que se hayan agregado manualmente después del backfill.
     */
    public function down(): void
    {
        $rows = DB::table('users')
            ->whereNotNull('department_id')
            ->select('id as user_id', 'department_id')
            ->get();

        foreach ($rows as $row) {
            DB::table('department_user')
                ->where('user_id', $row->user_id)
                ->where('department_id', $row->department_id)
                ->delete();
        }
    }
};
