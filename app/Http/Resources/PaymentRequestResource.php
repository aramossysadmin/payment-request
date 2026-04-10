<?php

namespace App\Http\Resources;

use App\Models\PaymentRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PaymentRequest */
class PaymentRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'folio_number' => $this->folio_number,
            'provider' => $this->provider,
            'rfc' => $this->rfc,
            'invoice_folio' => $this->invoice_folio,
            'description' => $this->description,
            'payment_type' => [
                'id' => $this->paymentType?->id,
                'name' => $this->paymentType?->name,
                'slug' => $this->paymentType?->slug,
                'invoice_documents_mode' => $this->paymentType?->invoice_documents_mode?->value ?? 'disabled',
                'additional_documents_mode' => $this->paymentType?->additional_documents_mode?->value ?? 'optional',
            ],
            'advance_documents' => $this->advance_documents,
            'status' => [
                'name' => $this->status::$name,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
            ],
            'iva_rate' => [
                'value' => $this->iva_rate->value,
                'label' => $this->iva_rate->label(),
            ],
            'subtotal' => (string) $this->subtotal,
            'iva' => (string) $this->iva,
            'retention' => (string) $this->retention,
            'total' => (string) $this->total,
            'number_purchase_invoices' => $this->number_purchase_invoices,
            'number_vendor_payments' => $this->number_vendor_payments,
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ],
            'department' => [
                'id' => $this->department?->id,
                'name' => $this->department?->name,
            ],
            'currency' => [
                'id' => $this->currency?->id,
                'name' => $this->currency?->name,
                'prefix' => $this->currency?->prefix,
            ],
            'branch' => [
                'id' => $this->branch?->id,
                'name' => $this->branch?->name,
            ],
            'expense_concept' => [
                'id' => $this->expenseConcept?->id,
                'name' => $this->expenseConcept?->name,
            ],
            'approvals' => PaymentRequestApprovalResource::collection($this->whenLoaded('approvals')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
