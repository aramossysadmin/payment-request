<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega el campo cost_center (Centro de Costos / CeCo) a la tabla branches.
     *
     * - nullable: las 45 sucursales existentes no tienen CeCo capturado todavía;
     *   el mapeo se hará gradualmente desde el panel admin.
     * - Sin unique por ahora: la regla operativa aún no confirma si el CeCo debe
     *   ser único entre sucursales. Si más adelante se confirma, agregar en una
     *   nueva migration un $table->unique('cost_center') (previa limpieza de
     *   duplicados) y ->unique(ignoreRecord: true) en el TextInput del form.
     */
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('cost_center', 50)->nullable()->after('society_id');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('cost_center');
        });
    }
};
