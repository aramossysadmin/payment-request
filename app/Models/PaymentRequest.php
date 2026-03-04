<?php

namespace App\Models;

use App\Enums\PaymentType;
use App\States\PaymentRequest\PaymentRequestState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\ModelStates\HasStates;

class PaymentRequest extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentRequestFactory> */
    use HasFactory, HasStates, SoftDeletes;

    protected $fillable = [
        'user_id',
        'department_id',
        'folio_number',
        'provider',
        'invoice_folio',
        'currency_id',
        'branch_id',
        'expense_concept_id',
        'description',
        'payment_type',
        'advance_documents',
        'status',
        'subtotal',
        'iva',
        'retention',
        'total',
        'number_purchase_invoices',
        'number_vendor_payments',
    ];

    protected static function booted(): void
    {
        static::creating(function (PaymentRequest $paymentRequest) {
            if (! $paymentRequest->uuid) {
                $paymentRequest->uuid = (string) Str::uuid();
            }

            if (! $paymentRequest->folio_number) {
                $paymentRequest->folio_number = (static::max('folio_number') ?? 0) + 1;
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
            'status' => PaymentRequestState::class,
            'payment_type' => PaymentType::class,
            'advance_documents' => 'array',
            'subtotal' => 'decimal:2',
            'iva' => 'decimal:2',
            'retention' => 'boolean',
            'total' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function expenseConcept(): BelongsTo
    {
        return $this->belongsTo(ExpenseConcept::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(PaymentRequestApproval::class);
    }
}
