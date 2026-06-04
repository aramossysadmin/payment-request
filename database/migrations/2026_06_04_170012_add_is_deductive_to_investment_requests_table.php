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
        Schema::table('investment_requests', function (Blueprint $table) {
            $table->boolean('is_deductive')->default(false)->after('is_addendum');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('investment_requests', function (Blueprint $table) {
            $table->dropColumn('is_deductive');
        });
    }
};
