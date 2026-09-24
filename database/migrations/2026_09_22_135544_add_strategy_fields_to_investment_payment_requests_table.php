<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investment_payment_requests', function (Blueprint $table) {
            $table->foreignId('strategy_id')->nullable()->after('expense_concept_id')->constrained('strategies')->nullOnDelete();
            $table->decimal('strategy_amount', 14, 2)->nullable()->after('strategy_id');
            $table->decimal('strategy_factor_snapshot', 8, 6)->nullable()->after('strategy_amount');
            $table->string('strategy_name_snapshot', 150)->nullable()->after('strategy_factor_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('investment_payment_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('strategy_id');
            $table->dropColumn(['strategy_amount', 'strategy_factor_snapshot', 'strategy_name_snapshot']);
        });
    }
};
