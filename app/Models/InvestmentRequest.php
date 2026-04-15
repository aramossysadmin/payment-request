<?php

namespace App\Models;

use App\Enums\IvaRate;
use App\States\InvestmentRequest\InvestmentRequestState;
use Database\Factories\InvestmentRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\ModelStates\HasStates;

class InvestmentRequest extends Model
{
    /** @use HasFactory<InvestmentRequestFactory> */
    use HasFactory, HasStates, SoftDeletes;

    protected $fillable = [
        'folio_number',
        'provider',
        'rfc',
        'invoice_folio',
        'currency_id',
        'branch_id',
        'expense_concept_id',
        'description',
        'payment_type_id',
        'advance_documents',
        'subtotal',
        'iva_rate',
        'iva',
        'retention',
        'total',
        'number_purchase_invoices',
        'number_vendor_payments',
    ];

    protected function setProviderAttribute(string $value): void
    {
        $this->attributes['provider'] = mb_strtoupper(trim($value));
    }

    protected function setRfcAttribute(?string $value): void
    {
        $this->attributes['rfc'] = $value ? mb_strtoupper(trim($value)) : null;
    }

    protected function setInvoiceFolioAttribute(string $value): void
    {
        $this->attributes['invoice_folio'] = mb_strtoupper(trim($value));
    }

    protected function setDescriptionAttribute(?string $value): void
    {
        if ($value === null) {
            $this->attributes['description'] = null;

            return;
        }

        $trimmed = trim($value);
        $this->attributes['description'] = mb_strtoupper(mb_substr($trimmed, 0, 1)).mb_substr($trimmed, 1);
    }

    protected static function booted(): void
    {
        static::creating(function (InvestmentRequest $investmentRequest) {
            if (! $investmentRequest->uuid) {
                $investmentRequest->uuid = (string) Str::uuid();
            }

            if (! $investmentRequest->folio_number) {
                $investmentRequest->folio_number = (static::withTrashed()->max('folio_number') ?? 0) + 1;
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
            'status' => InvestmentRequestState::class,
            'iva_rate' => IvaRate::class,
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

    public function paymentType(): BelongsTo
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function expenseConcept(): BelongsTo
    {
        return $this->belongsTo(ExpenseConcept::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(InvestmentRequestApproval::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole('super_admin')) {
            return $query;
        }

        if ($user->authorizedDepartments()->exists()) {
            $authorizedDepartmentIds = $user->authorizedDepartments()->pluck('id');

            return $query->where(function ($q) use ($user, $authorizedDepartmentIds) {
                $q->whereIn('department_id', $authorizedDepartmentIds)
                    ->orWhere('user_id', $user->id)
                    ->orWhereHas('approvals', function ($approvalQuery) use ($user) {
                        $approvalQuery->where('user_id', $user->id);
                    });
            });
        }

        return $query->where('user_id', $user->id);
    }
}
