<?php

namespace App\Models;

use App\Enums\PaymentRequestStatus;
use App\Enums\PaymentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentRequest extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentRequestFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
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
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PaymentRequestStatus::class,
            'payment_type' => PaymentType::class,
            'advance_documents' => 'array',
            'subtotal' => 'decimal:2',
            'iva' => 'decimal:2',
            'retention' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
}
