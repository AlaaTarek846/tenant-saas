<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'invoice_id' => $this->invoice_id,
            'payment_method' => $this->payment_method,
            'reference' => $this->reference,
            'amount' => $this->amount,
            'paid_at' => $this->paid_at,
            'status' => $this->status?->value ?? $this->status,
            'invoice' => new InvoiceResource($this->whenLoaded('invoice')),
            'journal_entries' => JournalEntryResource::collection($this->whenLoaded('journalEntries')),
            'created_at' => $this->created_at?->format('Y-m-d'),
        ];
    }
}
