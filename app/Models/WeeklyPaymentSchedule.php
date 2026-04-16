<?php

namespace App\Models;

use Database\Factories\WeeklyPaymentScheduleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class WeeklyPaymentSchedule extends Model
{
    /** @use HasFactory<WeeklyPaymentScheduleFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'week_number',
        'year',
        'created_by',
        'status',
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
            'week_number' => 'integer',
            'year' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(WeeklyPaymentScheduleItem::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(WeeklyPaymentScheduleApproval::class);
    }
}
