<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega credenciales SAP B1 + toggle is_active a la tabla branches.
     *
     * - 4 campos SAP: nullable en BD para no romper las 45 sucursales existentes;
     *   el form Filament los pedirá como required al editar.
     * - sap_password: text porque encrypted cast genera strings largos.
     * - is_active: default true para que sucursales actuales sigan operativas.
     */
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('sap_database')->nullable()->after('society_id');
            $table->string('sap_branch_id')->nullable()->after('sap_database');
            $table->string('sap_user')->nullable()->after('sap_branch_id');
            $table->text('sap_password')->nullable()->after('sap_user');
            $table->boolean('is_active')->default(true)->after('sap_password');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn([
                'sap_database',
                'sap_branch_id',
                'sap_user',
                'sap_password',
                'is_active',
            ]);
        });
    }
};
