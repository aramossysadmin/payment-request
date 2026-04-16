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
        Schema::create('weekly_payment_schedule_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weekly_payment_schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('investment_payment_request_id')->constrained()->cascadeOnDelete();
            $table->boolean('included')->default(true);
            $table->text('exclusion_reason')->nullable();
            $table->timestamps();

            $table->unique(['weekly_payment_schedule_id', 'investment_payment_request_id'], 'schedule_payment_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_payment_schedule_items');
    }
};
