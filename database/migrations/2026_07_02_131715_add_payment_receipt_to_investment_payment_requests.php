<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investment_payment_requests', function (Blueprint $table) {
            $table->json('payment_receipt_documents')->nullable()->after('advance_documents');
            $table->timestamp('receipt_uploaded_at')->nullable()->after('payment_receipt_documents');
            $table->foreignId('receipt_uploaded_by')->nullable()->after('receipt_uploaded_at')->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::table('investment_payment_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('receipt_uploaded_by');
            $table->dropColumn(['payment_receipt_documents', 'receipt_uploaded_at']);
        });
    }
};
