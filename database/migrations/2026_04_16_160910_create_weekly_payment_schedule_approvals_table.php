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
        Schema::create('weekly_payment_schedule_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weekly_payment_schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->string('status')->default('pending');
            $table->text('comments')->nullable();
            $table->string('approval_token')->nullable()->unique();
            $table->timestamp('approval_token_expires_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_payment_schedule_approvals');
    }
};
