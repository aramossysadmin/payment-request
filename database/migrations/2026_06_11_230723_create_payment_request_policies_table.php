<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_request_policies', function (Blueprint $table) {
            $table->id();

            // Ventana de captura (CREAR / EDITAR drafts)
            $table->boolean('capture_window_is_active')->default(true);
            $table->string('capture_open_day')->default('monday');
            $table->time('capture_open_time')->default('08:00:00');
            $table->string('capture_close_day')->default('wednesday');
            $table->time('capture_close_time')->default('18:00:00');

            // Ventana de submit (ENVIAR a Autorización al CEO)
            $table->boolean('submit_window_is_active')->default(false); // OFF por default
            $table->string('submit_open_day')->default('monday');
            $table->time('submit_open_time')->default('08:00:00');
            $table->string('submit_close_day')->default('wednesday');
            $table->time('submit_close_time')->default('18:00:00');

            // Marcador para "drafts viejos inmunes a la cancelación automática"
            // Se setea cuando submit_window_is_active pasa a true por primera vez.
            $table->timestamp('cancellation_activated_at')->nullable();

            // Fecha de provisión del pago
            $table->string('provision_target_day')->default('tuesday');
            $table->unsignedTinyInteger('provision_target_week_offset')->default(1);

            // Roles
            $table->json('override_role_names')->default(json_encode(['super_admin']));
            $table->json('editor_role_names')->default(json_encode(['super_admin', 'project_manager']));

            // Comportamiento UX
            $table->unsignedTinyInteger('warning_minutes_before_close')->default(5);

            // Snooze (extiende ventana actual con motivo)
            $table->timestamp('capture_window_snoozed_until')->nullable();
            $table->string('capture_window_snooze_reason')->nullable();
            $table->timestamp('submit_window_snoozed_until')->nullable();
            $table->string('submit_window_snooze_reason')->nullable();

            // Reservado para V2
            $table->json('holidays')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_request_policies');
    }
};
