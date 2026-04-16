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
        Schema::create('investment_payment_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('folio_number')->unique();
            $table->foreignId('investment_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('department_id')->constrained();
            $table->string('provider');
            $table->string('rfc', 13)->nullable();
            $table->string('invoice_folio')->nullable();
            $table->foreignId('currency_id')->constrained();
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('expense_concept_id')->constrained();
            $table->foreignId('payment_type_id')->constrained();
            $table->text('description')->nullable();
            $table->string('status')->default('pending_approval');
            $table->decimal('subtotal', 14, 2);
            $table->string('iva_rate', 4);
            $table->decimal('iva', 14, 2);
            $table->boolean('retention')->default(false);
            $table->decimal('total', 14, 2);
            $table->json('advance_documents')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_payment_requests');
    }
};
