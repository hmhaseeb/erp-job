@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between gap-2 mb-4 no-print">
        <a href="{{ route('purchase.invoices') }}" class="btn-sm bg-white border border-border-color text-title"><i class="ph ph-arrow-left me-1"></i> Back to Purchase Bills</a>
        <button type="button" onclick="window.print()" class="btn-sm bg-dark text-white"><i class="ph ph-printer me-1"></i> Print Purchase Bill</button>
    </div>

    <div class="bg-white border border-border-color rounded-md p-6 sm:p-8">
        <div class="flex flex-wrap justify-between items-start gap-4 mb-6 pb-6 border-b border-border-color">
            <div>
                @if (erp_setting('show_logo_on_docs', true))
                    <img src="{{ erp_logo('light') }}" class="h-10 mb-3" alt="logo">
                @endif
                <h2 class="text-lg font-bold text-title mb-0">{{ erp_setting('company_name') }}</h2>
                <p class="text-xs text-default mb-0">{{ erp_setting('address_line1') }}, {{ erp_setting('city') }}, {{ erp_setting('country') }}</p>
                <p class="text-xs text-default mb-0">{{ erp_setting('email') }} · {{ erp_setting('phone') }}</p>
            </div>
            <div class="text-left sm:text-right">
                <h1 class="text-xl font-bold text-title uppercase mb-1">Purchase Bill / Voucher</h1>
                <p class="text-sm font-semibold text-primary mb-1">{{ $bill->bill_no }}</p>
                <span class="text-[11px] px-2.5 py-0.5 rounded {{ $bill->status === 'paid' ? 'bg-success text-white' : 'bg-warning text-white' }}">{{ strtoupper($bill->status) }}</span>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6 pb-4 border-b border-border-color text-sm">
            <div>
                <p class="text-xs uppercase font-semibold text-default mb-1">Vendor / Supplier</p>
                <p class="font-bold text-title mb-0">{{ $bill->supplier?->name }}</p>
                <p class="text-xs text-default mb-0">{{ $bill->supplier?->company_name }}</p>
                <p class="text-xs text-default mb-0">{{ $bill->supplier?->address }}</p>
                <p class="text-xs text-default mb-0">{{ $bill->supplier?->phone }} · {{ $bill->supplier?->email }}</p>
            </div>
            <div class="text-left sm:text-right text-xs space-y-1">
                <div><span class="text-default">Bill Date:</span> <strong class="text-title">{{ format_date($bill->invoice_date) }}</strong></div>
                <div><span class="text-default">Due Date:</span> <strong class="text-title">{{ format_date($bill->due_date) }}</strong></div>
                @if ($bill->vendor_invoice_no)<div><span class="text-default">Vendor Inv #:</span> <strong class="text-title">{{ $bill->vendor_invoice_no }}</strong></div>@endif
                @if ($bill->purchaseOrder)<div><span class="text-default">PO #:</span> <strong class="text-title">{{ $bill->purchaseOrder->po_no }}</strong></div>@endif
            </div>
        </div>

        <div class="overflow-x-auto mb-6">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs bg-light border-b border-border-color">
                        <th class="text-left py-2.5 px-3">Item / Description</th>
                        <th class="text-right py-2.5 px-3">Qty</th>
                        <th class="text-right py-2.5 px-3">Unit Cost</th>
                        <th class="text-right py-2.5 px-3">Tax</th>
                        <th class="text-right py-2.5 px-3">Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bill->items as $item)
                        <tr class="border-b border-border-color last:border-0">
                            <td class="py-2.5 px-3 font-medium text-title">{{ $item->description }}</td>
                            <td class="py-2.5 px-3 text-right">{{ $item->quantity }}</td>
                            <td class="py-2.5 px-3 text-right">{{ format_currency($item->unit_cost) }}</td>
                            <td class="py-2.5 px-3 text-right">{{ format_currency($item->tax_amount) }}</td>
                            <td class="py-2.5 px-3 text-right font-semibold text-title">{{ format_currency($item->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex justify-end mb-6">
            <div class="w-72 text-sm space-y-2">
                <div class="flex justify-between"><span class="text-default">Subtotal</span><span class="font-semibold">{{ format_currency($bill->subtotal) }}</span></div>
                <div class="flex justify-between"><span class="text-default">{{ erp_setting('tax_label', 'VAT') }}</span><span class="font-semibold">{{ format_currency($bill->tax_amount) }}</span></div>
                @if ($bill->discount_amount > 0)
                    <div class="flex justify-between text-danger"><span>Discount</span><span>-{{ format_currency($bill->discount_amount) }}</span></div>
                @endif
                @if ($bill->shipping_charge > 0)
                    <div class="flex justify-between"><span class="text-default">Shipping</span><span>{{ format_currency($bill->shipping_charge) }}</span></div>
                @endif
                <div class="flex justify-between border-t border-border-color pt-2 text-base font-bold text-title"><span>Grand Total</span><span>{{ format_currency($bill->total_amount) }}</span></div>
                <div class="flex justify-between text-xs"><span class="text-default">Paid</span><span class="text-success font-semibold">{{ format_currency($bill->paid_amount) }}</span></div>
                <div class="flex justify-between text-xs"><span class="text-default">Balance Due</span><span class="text-danger font-bold">{{ format_currency($bill->balance_due) }}</span></div>
            </div>
        </div>

        @if ($bill->notes)
            <div class="border-t border-border-color pt-4 text-xs text-default"><strong>Notes:</strong> {{ $bill->notes }}</div>
        @endif
    </div>
</div>
@endsection
