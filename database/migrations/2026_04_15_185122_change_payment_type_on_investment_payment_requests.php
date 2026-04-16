<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('investment_payment_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_type_id');
            $table->string('payment_type', 20)->default('anticipo')->after('expense_concept_id');
        });
    }

    public function down(): void
    {
        Schema::table('investment_payment_requests', function (Blueprint $table) {
            $table->dropColumn('payment_type');
            $table->foreignId('payment_type_id')->after('expense_concept_id')->constrained();
        });
    }
};
