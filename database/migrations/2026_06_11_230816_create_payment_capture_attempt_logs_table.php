<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_capture_attempt_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('attempted_at');

            // 'create' | 'edit' | 'submit' | 'snooze_extended'
            $table->string('action');

            // true = intento bloqueado (fuera de ventana); false = snooze ejecutado, etc.
            $table->boolean('was_blocked')->default(true);

            $table->string('ip_address')->nullable();

            // Snapshot de la política al momento del intento (para auditoría histórica)
            $table->json('policy_snapshot')->nullable();

            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'attempted_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_capture_attempt_logs');
    }
};
