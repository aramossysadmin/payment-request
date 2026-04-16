<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investment_requests', function (Blueprint $table) {
            $table->foreignId('investment_expense_concept_id')->nullable()->after('expense_concept_id')->constrained();
        });
    }

    public function down(): void
    {
        Schema::table('investment_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('investment_expense_concept_id');
        });
    }
};
