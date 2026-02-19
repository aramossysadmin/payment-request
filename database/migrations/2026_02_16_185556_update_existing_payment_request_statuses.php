<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('payment_requests')
            ->where('status', 'pending')
            ->update(['status' => 'pending_approval']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('payment_requests')
            ->where('status', 'pending_approval')
            ->update(['status' => 'pending']);
    }
};
