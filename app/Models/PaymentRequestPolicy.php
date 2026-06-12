<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentRequestPolicy extends Model
{
    protected $fillable = [
        'capture_window_is_active',
        'capture_open_day',
        'capture_open_time',
        'capture_close_day',
        'capture_close_time',
        'submit_window_is_active',
        'submit_open_day',
        'submit_open_time',
        'submit_close_day',
        'submit_close_time',
        'cancellation_activated_at',
        'provision_target_day',
        'provision_target_week_offset',
        'override_role_names',
        'editor_role_names',
        'warning_minutes_before_close',
        'capture_window_snoozed_until',
        'capture_window_snooze_reason',
        'submit_window_snoozed_until',
        'submit_window_snooze_reason',
        'holidays',
    ];

    protected function casts(): array
    {
        return [
            'capture_window_is_active' => 'boolean',
            'submit_window_is_active' => 'boolean',
            'capture_open_time' => 'string',
            'capture_close_time' => 'string',
            'submit_open_time' => 'string',
            'submit_close_time' => 'string',
            'cancellation_activated_at' => 'datetime',
            'capture_window_snoozed_until' => 'datetime',
            'submit_window_snoozed_until' => 'datetime',
            'override_role_names' => 'array',
            'editor_role_names' => 'array',
            'holidays' => 'array',
            'warning_minutes_before_close' => 'integer',
            'provision_target_week_offset' => 'integer',
        ];
    }

    /**
     * Devuelve el registro único (singleton) de la política. Si no existe, lo crea con defaults explícitos.
     */
    public static function current(): self
    {
        $existing = static::query()->first();
        if ($existing) {
            return $existing;
        }

        return static::query()->create([
            'capture_window_is_active' => true,
            'capture_open_day' => 'monday',
            'capture_open_time' => '08:00:00',
            'capture_close_day' => 'wednesday',
            'capture_close_time' => '18:00:00',
            'submit_window_is_active' => false,
            'submit_open_day' => 'monday',
            'submit_open_time' => '08:00:00',
            'submit_close_day' => 'wednesday',
            'submit_close_time' => '18:00:00',
            'provision_target_day' => 'tuesday',
            'provision_target_week_offset' => 1,
            'override_role_names' => ['super_admin'],
            'editor_role_names' => ['super_admin', 'project_manager'],
            'warning_minutes_before_close' => 5,
        ]);
    }
}
