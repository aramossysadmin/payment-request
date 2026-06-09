<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InvestmentPaymentApproval extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'investment_payment_request_id',
        'user_id',
        'status',
        'comments',
        'responded_at',
        'approval_token',
        'approval_token_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
            'approval_token_expires_at' => 'datetime',
        ];
    }

    public function investmentPaymentRequest(): BelongsTo
    {
        return $this->belongsTo(InvestmentPaymentRequest::class);
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
