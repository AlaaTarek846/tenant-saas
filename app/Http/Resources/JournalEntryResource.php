<?php

namespace App\Http\Resources;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $totalDebit = $this->whenLoaded('details', fn () => round((float) $this->details->sum('debit'), 2));
        $totalCredit = $this->whenLoaded('details', fn () => round((float) $this->details->sum('credit'), 2));

        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'description' => $this->description,
            'entry_date' => $this->entry_date?->format('Y-m-d'),
            'source' => $this->sourceType(),
            'source_label' => $this->sourceLabel(),
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'is_manual' => $this->reference_type === null,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'details' => JournalEntryDetailResource::collection($this->whenLoaded('details')),
            'created_at' => $this->created_at?->format('Y-m-d'),
        ];
    }

    protected function sourceType(): string
    {
        if ($this->reference_type === null) {
            return 'manual';
        }

        if ($this->reference_type === (new Invoice)->getMorphClass()) {
            return 'invoice';
        }

        if ($this->reference_type === (new Payment)->getMorphClass()) {
            return 'payment';
        }

        return 'system';
    }

    protected function sourceLabel(): string
    {
        return match ($this->sourceType()) {
            'manual' => 'يدوي',
            'invoice' => 'فاتورة',
            'payment' => 'دفعة',
            default => 'نظام',
        };
    }
}
