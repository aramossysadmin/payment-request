<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investment_payment_batches', function (Blueprint $table) {
            $table->uuid('final_ceo_approval_token')->nullable()->unique()->after('ceo_reviewed_at');
            $table->timestamp('final_ceo_approval_token_expires_at')->nullable()->after('final_ceo_approval_token');
            $table->timestamp('final_ceo_reviewed_at')->nullable()->after('final_ceo_approval_token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('investment_payment_batches', function (Blueprint $table) {
            $table->dropUnique(['final_ceo_approval_token']);
            $table->dropColumn(['final_ceo_approval_token', 'final_ceo_approval_token_expires_at', 'final_ceo_reviewed_at']);
        });
    }
};
