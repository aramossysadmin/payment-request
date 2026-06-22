<?php

namespace App\Models;

use App\Enums\IvaRate;
use App\States\PaymentRequest\PaymentRequestState;
use Database\Factories\PaymentRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\ModelStates\HasStates;

class PaymentRequest extends Model
{
    /** @use HasFactory<PaymentRequestFactory> */
    use HasFactory, HasStates, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->dontSubmitEmptyLogs();
    }

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

        $this->attributes['description'] = mb_strtoupper(trim($value));
    }

    protected static function booted(): void
    {
        static::creating(function (PaymentRequest $paymentRequest) {
            if (! $paymentRequest->uuid) {
                $paymentRequest->uuid = (string) Str::uuid();
            }

            if (! $paymentRequest->folio_number) {
                $paymentRequest->folio_number = (static::withTrashed()->max('folio_number') ?? 0) + 1;
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
        return $this->hasMany(PaymentRequestApproval::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole('super_admin')) {
            return $query;
        }

        // Departamentos visibles = autorización (level 1/2) + pertenencia (pivot).
        // Mismo patrón que InvestmentRequest::scopeVisibleTo para soportar multi-dpto
        // incluso cuando el user también es autorizador de algún dpto.
        $authorizedDepartmentIds = $user->authorizedDepartments()->pluck('id')->all();
        $userDepartmentIds = $user->departments()->pluck('departments.id')->all();
        $allVisibleDepartmentIds = array_values(array_unique(array_merge($authorizedDepartmentIds, $userDepartmentIds)));

        return $query->where(function ($q) use ($user, $allVisibleDepartmentIds) {
            $q->where('user_id', $user->id);

            if (! empty($allVisibleDepartmentIds)) {
                $q->orWhereIn('department_id', $allVisibleDepartmentIds);
            }

            $q->orWhereHas('approvals', function ($approvalQuery) use ($user) {
                $approvalQuery->where('user_id', $user->id);
            });
        });
    }
}
