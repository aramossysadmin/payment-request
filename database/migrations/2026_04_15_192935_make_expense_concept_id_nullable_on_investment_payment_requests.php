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
            $table->foreignId('expense_concept_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('investment_payment_requests', function (Blueprint $table) {
            $table->foreignId('expense_concept_id')->nullable(false)->change();
        });
    }
};
