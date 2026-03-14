<?php

namespace App\Models;

use Database\Factories\PaymentRequestApprovalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRequestApproval extends Model
{
    /** @use HasFactory<PaymentRequestApprovalFactory> */
    use HasFactory;

    protected $fillable = [
        'payment_request_id',
        'user_id',
        'stage',
        'status',
        'comments',
        'responded_at',
        'approval_token',
        'approval_token_expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
            'approval_token_expires_at' => 'datetime',
        ];
    }

    public function hasValidToken(): bool
    {
        return $this->approval_token
            && $this->approval_token_expires_at
            && $this->approval_token_expires_at->isFuture();
    }

    public function isTokenExpired(): bool
    {
        return $this->approval_token_expires_at
            && $this->approval_token_expires_at->isPast();
    }

    public function paymentRequest(): BelongsTo
    {
        return $this->belongsTo(PaymentRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
