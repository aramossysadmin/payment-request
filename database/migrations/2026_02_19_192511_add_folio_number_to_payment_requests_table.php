<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->unsignedInteger('folio_number')->nullable()->unique()->after('id');
        });

        DB::table('payment_requests')
            ->orderBy('id')
            ->eachById(function ($record) {
                static $folio = 0;
                $folio++;
                DB::table('payment_requests')
                    ->where('id', $record->id)
                    ->update(['folio_number' => $folio]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn('folio_number');
        });
    }
};
