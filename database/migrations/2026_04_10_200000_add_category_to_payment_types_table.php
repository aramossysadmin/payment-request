<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_types', function (Blueprint $table) {
            $table->string('category')->default('payment')->after('is_active');
        });

        DB::table('payment_types')
            ->where('slug', 'investment')
            ->update(['category' => 'investment']);
    }

    public function down(): void
    {
        Schema::table('payment_types', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
