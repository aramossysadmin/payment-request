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
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\ModelStates\HasStates;

class InvestmentRequest extends Model
{
    /** @use HasFactory<InvestmentRequestFactory> */
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
        'contact_name',
        'contact_email',
        'contact_phone',
        'invoice_folio',
        'currency_id',
        'branch_id',
        'expense_concept_id',
        'investment_expense_concept_id',
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

    protected function setProviderAttribute(?string $value): void
    {
        $this->attributes['provider'] = $value ? mb_strtoupper(trim($value)) : null;
    }

    protected function setRfcAttribute(?string $value): void
    {
        $this->attributes['rfc'] = $value ? mb_strtoupper(trim($value)) : null;
    }

    protected function setInvoiceFolioAttribute(?string $value): void
    {
        $this->attributes['invoice_folio'] = $value ? mb_strtoupper(trim($value)) : null;
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

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
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

    public function investmentExpenseConcept(): BelongsTo
    {
        return $this->belongsTo(InvestmentExpenseConcept::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(InvestmentRequestApproval::class);
    }

    public function investmentPaymentRequests(): HasMany
    {
        return $this->hasMany(InvestmentPaymentRequest::class);
    }

    public function getRemainingBalanceAttribute(): string
    {
        $paid = $this->investmentPaymentRequests()
            ->whereIn('status', ['pending_approval', 'approved'])
            ->sum('total');

        return bcsub($this->total, (string) $paid, 2);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole('super_admin')) {
            return $query;
        }

        // Departamentos visibles = autorización (level 1/2) + pertenencia (pivot).
        // Unificación necesaria para users multi-dpto que también son autorizadores
        // de algún dpto: deben ver todas las solicitudes de sus dptos asignados,
        // no solo de los dptos donde son autorizadores.
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
