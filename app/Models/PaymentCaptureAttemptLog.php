<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentCaptureAttemptLog extends Model
{
    protected $fillable = [
        'user_id',
        'attempted_at',
        'action',
        'was_blocked',
        'ip_address',
        'policy_snapshot',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'attempted_at' => 'datetime',
            'was_blocked' => 'boolean',
            'policy_snapshot' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
