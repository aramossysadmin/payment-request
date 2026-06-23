<?php

namespace App\Models;

use Database\Factories\InvestmentExpenseConceptFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InvestmentExpenseConcept extends Model
{
    /** @use HasFactory<InvestmentExpenseConceptFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'name',
        'investment_expense_category_id',
        'sap_item_code',
        'is_active',
    ];

    protected function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = mb_strtoupper(trim($value));
    }

    /**
     * ItemCode de SAP B1 — siempre uppercase + trim. NULL si vacío.
     */
    protected function setSapItemCodeAttribute(?string $value): void
    {
        $trimmed = $value !== null ? trim($value) : null;
        $this->attributes['sap_item_code'] = filled($trimmed) ? mb_strtoupper($trimmed) : null;
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(InvestmentExpenseCategory::class, 'investment_expense_category_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * True si el concepto está mapeado a un ItemCode de SAP B1.
     * Usar como verificación previa antes de enviar pagos relacionados a SAP.
     */
    public function isSapMapped(): bool
    {
        return filled($this->sap_item_code);
    }
}
