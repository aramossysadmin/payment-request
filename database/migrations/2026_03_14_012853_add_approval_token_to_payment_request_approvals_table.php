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
        Schema::table('payment_request_approvals', function (Blueprint $table) {
            $table->string('approval_token')->nullable()->unique()->after('responded_at');
            $table->timestamp('approval_token_expires_at')->nullable()->after('approval_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_request_approvals', function (Blueprint $table) {
            $table->dropUnique(['approval_token']);
            $table->dropColumn(['approval_token', 'approval_token_expires_at']);
        });
    }
};
