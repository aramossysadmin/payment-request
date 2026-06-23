<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega sap_item_code (ItemCode de SAP B1) a la tabla de conceptos.
     *
     * - Nullable en BD para no romper los 138 conceptos existentes;
     *   el form Filament lo pedirá como required al editar.
     * - Unique (con NULLs múltiples permitidos): dos conceptos del portal
     *   NO pueden apuntar al mismo ItemCode SAP. Si en el futuro se requiere
     *   permitir duplicados, basta con un `$table->dropUnique(['sap_item_code'])`.
     */
    public function up(): void
    {
        Schema::table('investment_expense_concepts', function (Blueprint $table) {
            $table->string('sap_item_code', 50)->nullable()->after('investment_expense_category_id');
            $table->unique('sap_item_code');
        });
    }

    public function down(): void
    {
        Schema::table('investment_expense_concepts', function (Blueprint $table) {
            $table->dropUnique(['sap_item_code']);
            $table->dropColumn('sap_item_code');
        });
    }
};
