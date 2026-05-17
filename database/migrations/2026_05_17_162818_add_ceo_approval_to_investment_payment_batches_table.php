<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investment_payment_batches', function (Blueprint $table) {
            $table->uuid('ceo_approval_token')->nullable()->unique()->after('deadline_at');
            $table->timestamp('ceo_approval_token_expires_at')->nullable()->after('ceo_approval_token');
            $table->timestamp('ceo_reviewed_at')->nullable()->after('ceo_approval_token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('investment_payment_batches', function (Blueprint $table) {
            $table->dropUnique(['ceo_approval_token']);
            $table->dropColumn(['ceo_approval_token', 'ceo_approval_token_expires_at', 'ceo_reviewed_at']);
        });
    }
};
