<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_requests', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->unsignedInteger('folio_number')->nullable()->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('provider');
            $table->string('rfc', 13)->nullable();
            $table->string('invoice_folio');
            $table->foreignId('currency_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('expense_concept_id')->constrained()->restrictOnDelete();
            $table->text('description')->nullable();
            $table->foreignId('payment_type_id')->nullable()->constrained('payment_types')->restrictOnDelete();
            $table->json('advance_documents')->nullable();
            $table->string('status')->default('pending_department');
            $table->decimal('subtotal', 12, 2);
            $table->string('iva_rate', 4)->default('0.16');
            $table->decimal('iva', 12, 2);
            $table->decimal('retention', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->unsignedBigInteger('number_purchase_invoices')->nullable();
            $table->unsignedBigInteger('number_vendor_payments')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_requests');
    }
};
