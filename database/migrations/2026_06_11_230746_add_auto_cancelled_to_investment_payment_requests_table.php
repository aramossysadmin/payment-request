<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investment_payment_requests', function (Blueprint $table) {
            $table->timestamp('auto_cancelled_at')->nullable()->after('final_reviewed_at');
            $table->string('auto_cancellation_reason')->nullable()->after('auto_cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('investment_payment_requests', function (Blueprint $table) {
            $table->dropColumn(['auto_cancelled_at', 'auto_cancellation_reason']);
        });
    }
};
