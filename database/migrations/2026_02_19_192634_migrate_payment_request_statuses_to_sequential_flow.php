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
            ->where('status', 'pending_approval')
            ->update(['status' => 'pending_department']);

        DB::table('payment_requests')
            ->where('status', 'approved')
            ->update(['status' => 'completed']);

        DB::table('payment_requests')
            ->where('status', 'rejected')
            ->update(['status' => 'pending_department']);

        DB::table('payment_requests')
            ->where('status', 'reopened')
            ->update(['status' => 'pending_department']);

        DB::table('payment_requests')
            ->where('status', 'paid')
            ->update(['status' => 'completed']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('payment_requests')
            ->where('status', 'pending_department')
            ->update(['status' => 'pending_approval']);

        DB::table('payment_requests')
            ->where('status', 'pending_administration')
            ->update(['status' => 'pending_approval']);

        DB::table('payment_requests')
            ->where('status', 'pending_treasury')
            ->update(['status' => 'pending_approval']);

        DB::table('payment_requests')
            ->where('status', 'completed')
            ->update(['status' => 'approved']);
    }
};
