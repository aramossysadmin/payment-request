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
        if (Schema::hasColumn('currencies', 'exchange_rate')) {
            return;
        }

        Schema::table('currencies', function (Blueprint $table) {
            $table->decimal('exchange_rate', 12, 4)->default(1)->after('prefix');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('currencies', 'exchange_rate')) {
            return;
        }

        Schema::table('currencies', function (Blueprint $table) {
            $table->dropColumn('exchange_rate');
        });
    }
};
