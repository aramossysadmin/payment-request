<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class InvestmentPaymentBatch extends Model
{
    protected $fillable = [
        'uuid',
        'department_id',
        'user_id',
        'project_id',
        'week_number',
        'year',
        'status',
        'submitted_at',
        'deadline_at',
        'ceo_approval_token',
        'ceo_approval_token_expires_at',
        'ceo_reviewed_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (! $model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'deadline_at' => 'datetime',
            'ceo_approval_token_expires_at' => 'datetime',
            'ceo_reviewed_at' => 'datetime',
            'week_number' => 'integer',
            'year' => 'integer',
        ];
    }

    public function hasValidCeoToken(): bool
    {
        return $this->ceo_approval_token !== null
            && $this->ceo_approval_token_expires_at !== null
            && $this->ceo_approval_token_expires_at->isFuture();
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function paymentRequests(): HasMany
    {
        return $this->hasMany(InvestmentPaymentRequest::class, 'batch_id');
    }
}
