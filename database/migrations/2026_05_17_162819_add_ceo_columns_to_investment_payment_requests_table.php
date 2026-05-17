<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investment_payment_requests', function (Blueprint $table) {
            $table->text('ceo_rejection_reason')->nullable()->after('status');
            $table->timestamp('ceo_reviewed_at')->nullable()->after('ceo_rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('investment_payment_requests', function (Blueprint $table) {
            $table->dropColumn(['ceo_rejection_reason', 'ceo_reviewed_at']);
        });
    }
};
