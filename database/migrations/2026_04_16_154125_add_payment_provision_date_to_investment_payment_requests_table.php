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
        Schema::table('investment_payment_requests', function (Blueprint $table) {
            $table->date('payment_provision_date')->nullable()->after('description');
            $table->unsignedTinyInteger('payment_week_number')->nullable()->after('payment_provision_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('investment_payment_requests', function (Blueprint $table) {
            $table->dropColumn(['payment_provision_date', 'payment_week_number']);
        });
    }
};
