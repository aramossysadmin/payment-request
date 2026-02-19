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
            $table->string('stage')->default('department')->after('user_id');
        });

        Schema::table('payment_request_approvals', function (Blueprint $table) {
            $table->dropUnique(['payment_request_id', 'user_id']);
            $table->unique(['payment_request_id', 'user_id', 'stage']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_request_approvals', function (Blueprint $table) {
            $table->dropUnique(['payment_request_id', 'user_id', 'stage']);
            $table->unique(['payment_request_id', 'user_id']);
            $table->dropColumn('stage');
        });
    }
};
