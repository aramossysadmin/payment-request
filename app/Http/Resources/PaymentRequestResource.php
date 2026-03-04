<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\PaymentRequest */
class PaymentRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'folio_number' => $this->folio_number,
            'provider' => $this->provider,
            'invoice_folio' => $this->invoice_folio,
            'description' => $this->description,
            'payment_type' => [
                'value' => $this->payment_type->value,
                'label' => $this->payment_type->label(),
            ],
            'advance_documents' => $this->advance_documents,
            'status' => [
                'name' => $this->status::$name,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
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
