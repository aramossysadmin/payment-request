<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investment_payment_requests', function (Blueprint $table) {
            $table->text('final_rejection_reason')->nullable()->after('pm_reviewed_at');
            $table->timestamp('final_reviewed_at')->nullable()->after('final_rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('investment_payment_requests', function (Blueprint $table) {
            $table->dropColumn(['final_rejection_reason', 'final_reviewed_at']);
        });
    }
};
