<?php

namespace App\Models;

use App\Enums\IvaRate;
use Database\Factories\InvestmentPaymentRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InvestmentPaymentRequest extends Model
{
    /** @use HasFactory<InvestmentPaymentRequestFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * Estados "Comprometido": el pago ya reserva presupuesto (desde el borrador) pero
     * aún NO se considera pagado. Cubre todo el flujo de captura/aprobación previo a
     * cargar documentos / programar el pago. Excluye rechazados y cancelados (liberan).
     *
     * @var array<int, string>
     */
    public const COMMITTED_STATUSES = ['draft', 'submitted', 'ceo_approved', 'projectmanager_review', 'projectmanager_approved', 'final_approved', 'documents_pending'];

    /**
     * Estados "Pagado": pago efectivo. Flujo nuevo desde que se cargan documentos
     * (completed), al programarse en banco (scheduled_for_bank) y al adjuntarse el
     * comprobante de pago (receipt_attached, fin del proceso), más los estados
     * legacy del flujo anterior (approved, pending_approval).
     *
     * @var array<int, string>
     */
    public const PAID_STATUSES = ['pending_approval', 'approved', 'completed', 'scheduled_for_bank', 'receipt_attached'];

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
        'investment_request_id',
        'batch_id',
        'provider',
        'rfc',
        'invoice_folio',
        'currency_id',
        'branch_id',
        'expense_concept_id',
        'payment_type',
        'description',
        'status',
        'advance_documents',
        'subtotal',
        'iva_rate',
        'iva',
        'payment_provision_date',
        'payment_week_number',
        'retention',
        'total',
        'approved_amount',
        'payment_receipt_documents',
        'receipt_uploaded_at',
        'receipt_uploaded_by',
        'ceo_rejection_reason',
        'ceo_reviewed_at',
        'pm_rejection_reason',
        'pm_reviewed_at',
        'final_rejection_reason',
        'final_reviewed_at',
    ];

    protected function setProviderAttribute(string $value): void
    {
        $this->attributes['provider'] = mb_strtoupper(trim($value));
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
        static::creating(function (self $model) {
            if (! $model->uuid) {
                $model->uuid = (string) Str::uuid();
            }

            if (! $model->folio_number) {
                $model->folio_number = (static::withTrashed()->max('folio_number') ?? 0) + 1;
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
            'payment_provision_date' => 'date',
            'payment_week_number' => 'integer',
            'iva_rate' => IvaRate::class,
            'advance_documents' => 'array',
            'payment_receipt_documents' => 'array',
            'receipt_uploaded_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'iva' => 'decimal:2',
            'retention' => 'boolean',
            'total' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'ceo_reviewed_at' => 'datetime',
            'pm_reviewed_at' => 'datetime',
            'final_reviewed_at' => 'datetime',
        ];
    }

    public function investmentRequest(): BelongsTo
    {
        return $this->belongsTo(InvestmentRequest::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InvestmentPaymentBatch::class, 'batch_id');
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
        return $this->hasMany(InvestmentPaymentApproval::class);
    }
}
