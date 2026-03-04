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
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('number_purchase_invoices')->nullable()->after('total');
            $table->unsignedBigInteger('number_vendor_payments')->nullable()->after('number_purchase_invoices');
        });
    }

    public function down(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn(['number_purchase_invoices', 'number_vendor_payments']);
        });
    }
};
