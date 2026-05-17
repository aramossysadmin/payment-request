<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investment_payment_requests', function (Blueprint $table) {
            $table->decimal('approved_amount', 14, 2)->nullable()->after('total');
            $table->text('pm_rejection_reason')->nullable()->after('ceo_reviewed_at');
            $table->timestamp('pm_reviewed_at')->nullable()->after('pm_rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('investment_payment_requests', function (Blueprint $table) {
            $table->dropColumn(['approved_amount', 'pm_rejection_reason', 'pm_reviewed_at']);
        });
    }
};
