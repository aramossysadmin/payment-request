<?php

namespace App\Models;

use Database\Factories\WeeklyPaymentScheduleApprovalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyPaymentScheduleApproval extends Model
{
    /** @use HasFactory<WeeklyPaymentScheduleApprovalFactory> */
    use HasFactory;

    protected $fillable = [
        'weekly_payment_schedule_id',
        'user_id',
        'status',
        'comments',
        'approval_token',
        'approval_token_expires_at',
        'responded_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'approval_token_expires_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(WeeklyPaymentSchedule::class, 'weekly_payment_schedule_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasValidToken(): bool
    {
        return $this->approval_token && ! $this->isTokenExpired();
    }

    public function isTokenExpired(): bool
    {
        return $this->approval_token_expires_at && $this->approval_token_expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
