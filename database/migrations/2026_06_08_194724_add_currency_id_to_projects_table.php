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
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('currency_id')->nullable()->after('branch_id')->constrained();
        });

        $mxnId = DB::table('currencies')->where('prefix', 'MXN')->value('id');

        if ($mxnId !== null) {
            DB::table('projects')->whereNull('currency_id')->update(['currency_id' => $mxnId]);
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedBigInteger('currency_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('currency_id');
        });
    }
};
