@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between gap-2 mb-4 no-print">
        <a href="{{ route('sales.invoices') }}" class="btn-sm bg-white border border-border-color text-title"><i class="ph ph-arrow-left me-1"></i> Back to Invoices</a>
        <button type="button" onclick="window.print()" class="btn-sm bg-dark text-white"><i class="ph ph-printer me-1"></i> Print Invoice</button>
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
                @if (erp_setting('tax_number'))
                    <p class="text-xs text-default mb-0">Tax/VAT Reg: {{ erp_setting('tax_number') }}</p>
                @endif
            </div>
            <div class="text-left sm:text-right">
                <h1 class="text-xl font-bold text-title uppercase mb-1">{{ $invoice->source === 'job_card' ? 'Service Tax Invoice' : 'Sales Invoice' }}</h1>
                <p class="text-sm font-semibold text-primary mb-1">{{ $invoice->invoice_no }}</p>
                <span class="text-[11px] px-2.5 py-0.5 rounded {{ $invoice->status === 'paid' ? 'bg-success text-white' : 'bg-warning text-white' }}">{{ strtoupper($invoice->status) }}</span>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6 pb-4 border-b border-border-color text-sm">
            <div>
                <p class="text-xs uppercase font-semibold text-default mb-1">Bill To</p>
                <p class="font-bold text-title mb-0">{{ $invoice->customer?->name }}</p>
                <p class="text-xs text-default mb-0">{{ $invoice->customer?->company_name }}</p>
                <p class="text-xs text-default mb-0">{{ $invoice->customer?->billing_address }}</p>
                <p class="text-xs text-default mb-0">{{ $invoice->customer?->phone }} · {{ $invoice->customer?->email }}</p>
            </div>
            <div class="text-left sm:text-right text-xs space-y-1">
                <div><span class="text-default">Invoice Date:</span> <strong class="text-title">{{ format_date($invoice->invoice_date) }}</strong></div>
                <div><span class="text-default">Due Date:</span> <strong class="text-title">{{ format_date($invoice->due_date) }}</strong></div>
                @if ($invoice->salesOrder)<div><span class="text-default">Sales Order:</span> <strong class="text-title">{{ $invoice->salesOrder->order_no }}</strong></div>@endif
                @if ($invoice->jobCard)<div><span class="text-default">Job Card:</span> <strong class="text-title">{{ $invoice->jobCard->job_card_no }}</strong></div>@endif
            </div>
        </div>

        <div class="overflow-x-auto mb-6">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs bg-light border-b border-border-color">
                        <th class="text-left py-2.5 px-3">Item / Description</th>
                        <th class="text-right py-2.5 px-3">Qty</th>
                        <th class="text-right py-2.5 px-3">Unit Price</th>
                        <th class="text-right py-2.5 px-3">Tax</th>
                        <th class="text-right py-2.5 px-3">Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->items as $item)
                        <tr class="border-b border-border-color last:border-0">
                            <td class="py-2.5 px-3 font-medium text-title">{{ $item->description }}</td>
                            <td class="py-2.5 px-3 text-right">{{ $item->quantity }}</td>
                            <td class="py-2.5 px-3 text-right">{{ format_currency($item->unit_price) }}</td>
                            <td class="py-2.5 px-3 text-right">{{ format_currency($item->tax_amount) }}</td>
                            <td class="py-2.5 px-3 text-right font-semibold text-title">{{ format_currency($item->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex justify-end mb-6">
            <div class="w-72 text-sm space-y-2">
                <div class="flex justify-between"><span class="text-default">Subtotal</span><span class="font-semibold">{{ format_currency($invoice->subtotal) }}</span></div>
                @if (erp_setting('show_tax_on_docs', true))
                    <div class="flex justify-between"><span class="text-default">{{ erp_setting('tax_label', 'VAT') }}</span><span class="font-semibold">{{ format_currency($invoice->tax_amount) }}</span></div>
                @endif
                @if ($invoice->discount_amount > 0)
                    <div class="flex justify-between text-danger"><span>Discount</span><span>-{{ format_currency($invoice->discount_amount) }}</span></div>
                @endif
                @if ($invoice->shipping_charge > 0)
                    <div class="flex justify-between"><span class="text-default">Shipping</span><span>{{ format_currency($invoice->shipping_charge) }}</span></div>
                @endif
                <div class="flex justify-between border-t border-border-color pt-2 text-base font-bold">
                    <span class="text-title">Total</span>
                    <span class="text-primary">{{ format_currency($invoice->total_amount) }}</span>
                </div>
                <div class="flex justify-between text-xs text-success"><span>Paid</span><span>{{ format_currency($invoice->paid_amount) }}</span></div>
                <div class="flex justify-between text-xs font-bold text-danger"><span>Balance Due</span><span>{{ format_currency($invoice->balance_due) }}</span></div>
            </div>
        </div>

        @if ($invoice->terms || erp_setting('invoice_footer_note'))
            <div class="border-t border-border-color pt-4 text-xs text-default space-y-1">
                @if ($invoice->terms)<p class="mb-0"><strong>Terms:</strong> {{ $invoice->terms }}</p>@endif
                <p class="mb-0">{{ erp_setting('invoice_footer_note') }}</p>
            </div>
        @endif
    </div>
</div>
@endsection
