<?php

namespace App\Models;

use Database\Factories\InvestmentRequestApprovalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestmentRequestApproval extends Model
{
    /** @use HasFactory<InvestmentRequestApprovalFactory> */
    use HasFactory;

    protected $fillable = [
        'investment_request_id',
        'user_id',
        'stage',
        'level',
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

    protected function setCommentsAttribute(?string $value): void
    {
        if ($value === null) {
            $this->attributes['comments'] = null;

            return;
        }

        $trimmed = trim($value);
        $this->attributes['comments'] = mb_strtoupper(mb_substr($trimmed, 0, 1)).mb_substr($trimmed, 1);
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

    public function investmentRequest(): BelongsTo
    {
        return $this->belongsTo(InvestmentRequest::class);
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
