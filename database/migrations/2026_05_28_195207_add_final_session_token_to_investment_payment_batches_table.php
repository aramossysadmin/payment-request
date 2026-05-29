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
        Schema::table('investment_payment_batches', function (Blueprint $table) {
            $table->uuid('final_session_token')->nullable()->after('final_ceo_approval_token_expires_at');
            $table->timestamp('final_session_token_expires_at')->nullable()->after('final_session_token');

            $table->index('final_session_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('investment_payment_batches', function (Blueprint $table) {
            $table->dropIndex(['final_session_token']);
            $table->dropColumn(['final_session_token', 'final_session_token_expires_at']);
        });
    }
};
