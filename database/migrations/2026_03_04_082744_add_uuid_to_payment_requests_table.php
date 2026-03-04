<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->char('uuid', 36)->nullable()->after('id');
        });

        DB::table('payment_requests')->whereNull('uuid')->eachById(function ($row) {
            DB::table('payment_requests')
                ->where('id', $row->id)
                ->update(['uuid' => (string) Str::uuid()]);
        });

        Schema::table('payment_requests', function (Blueprint $table) {
            $table->char('uuid', 36)->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
