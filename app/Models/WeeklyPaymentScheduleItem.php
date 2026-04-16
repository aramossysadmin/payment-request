<?php

namespace App\Models;

use Database\Factories\WeeklyPaymentScheduleItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyPaymentScheduleItem extends Model
{
    /** @use HasFactory<WeeklyPaymentScheduleItemFactory> */
    use HasFactory;

    protected $fillable = [
        'weekly_payment_schedule_id',
        'investment_payment_request_id',
        'included',
        'exclusion_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'included' => 'boolean',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(WeeklyPaymentSchedule::class, 'weekly_payment_schedule_id');
    }

    public function investmentPaymentRequest(): BelongsTo
    {
        return $this->belongsTo(InvestmentPaymentRequest::class);
    }
}
