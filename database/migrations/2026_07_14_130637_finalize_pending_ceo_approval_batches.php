<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            DB::table('investment_payment_batches')
                ->where('status', 'final_pending')
                ->update([
                    'status' => 'final_approved',
                    'final_ceo_reviewed_at' => DB::raw('COALESCE(final_ceo_reviewed_at, CURRENT_TIMESTAMP)'),
                    'final_ceo_approval_token' => null,
                    'final_ceo_approval_token_expires_at' => null,
                    'final_session_token' => null,
                    'final_session_token_expires_at' => null,
                ]);

            DB::table('investment_payment_requests')
                ->where('status', 'projectmanager_approved')
                ->update([
                    'status' => 'final_approved',
                    'final_reviewed_at' => DB::raw('COALESCE(final_reviewed_at, CURRENT_TIMESTAMP)'),
                ]);
        });
    }

    public function down(): void
    {
        // no-op: nunca revertir la promoción de batches finalizados.
    }
};
