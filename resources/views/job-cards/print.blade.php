@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between gap-2 mb-4 no-print">
        <a href="{{ route('job-cards.show', $jobCard->id) }}" class="btn-sm bg-white border border-border-color text-title"><i class="ph ph-arrow-left me-1"></i> Back to Job Card</a>
        <button type="button" onclick="window.print()" class="btn-sm bg-dark text-white"><i class="ph ph-printer me-1"></i> Print Job Card Slip</button>
    </div>

    <div class="bg-white border border-border-color rounded-md p-6 sm:p-8">
        <div class="flex flex-wrap justify-between items-start gap-4 mb-6 pb-4 border-b border-border-color">
            <div>
                @if (erp_setting('show_logo_on_docs', true))
                    <img src="{{ erp_logo('light') }}" class="h-10 mb-2" alt="logo">
                @endif
                <h2 class="text-lg font-bold text-title mb-0">{{ erp_setting('company_name') }}</h2>
                <p class="text-xs text-default mb-0">{{ erp_setting('address_line1') }}, {{ erp_setting('city') }} · {{ erp_setting('phone') }}</p>
            </div>
            <div class="text-right">
                <h1 class="text-xl font-bold text-title uppercase mb-1">Service Job Card</h1>
                <p class="text-sm font-semibold text-primary mb-1">{{ $jobCard->job_card_no }}</p>
                <span class="text-xs px-2 py-0.5 rounded bg-light border border-border-color capitalize">{{ str_replace('_', ' ', $jobCard->status) }}</span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-6 text-sm border-b border-border-color pb-4">
            <div>
                <p class="text-xs font-bold uppercase text-default mb-1">Customer Details</p>
                <p class="font-bold text-title mb-0">{{ $jobCard->customer?->name }}</p>
                <p class="text-xs text-default mb-0">{{ $jobCard->customer?->phone }} · {{ $jobCard->customer?->email }}</p>
                <p class="text-xs text-default mb-0">{{ $jobCard->customer?->billing_address }}</p>
            </div>
            <div>
                <p class="text-xs font-bold uppercase text-default mb-1">Device &amp; Service Info</p>
                <p class="font-bold text-title mb-0">{{ $jobCard->item_name }} ({{ $jobCard->brand_model }})</p>
                <p class="text-xs text-default mb-0">Serial #: {{ $jobCard->serial_number ?: 'N/A' }} · Received: {{ format_date($jobCard->received_date) }}</p>
                <p class="text-xs text-default mb-0">Technician: {{ $jobCard->technician?->full_name ?? 'Unassigned' }} · Mode: {{ $jobCard->parts_source === 'site' ? 'On-Site' : 'Direct Shop' }}</p>
            </div>
        </div>

        <div class="mb-6">
            <h3 class="text-xs font-bold uppercase text-default mb-1">Service Request &amp; Diagnosis</h3>
            <p class="text-sm text-title mb-2">{{ $jobCard->service_description }}</p>
            @if ($jobCard->diagnosis_notes)
                <p class="text-xs text-default mb-0"><strong>Diagnosis:</strong> {{ $jobCard->diagnosis_notes }}</p>
            @endif
        </div>

        <div class="mb-6">
            <h3 class="text-xs font-bold uppercase text-default mb-2">Parts &amp; Charges</h3>
            <table class="w-full text-sm border border-border-color">
                <thead>
                    <tr class="text-xs bg-light border-b border-border-color">
                        <th class="text-left p-2">Item / Service</th>
                        <th class="text-right p-2">Qty</th>
                        <th class="text-right p-2">Rate</th>
                        <th class="text-right p-2">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($jobCard->labor_charges > 0)
                        <tr class="border-b border-border-color"><td class="p-2">Service / Labor Charges</td><td class="p-2 text-right">1</td><td class="p-2 text-right">{{ format_currency($jobCard->labor_charges) }}</td><td class="p-2 text-right font-semibold">{{ format_currency($jobCard->labor_charges) }}</td></tr>
                    @endif
                    @foreach ($jobCard->parts as $part)
                        <tr class="border-b border-border-color"><td class="p-2">{{ $part->part_name }}</td><td class="p-2 text-right">{{ $part->quantity }}</td><td class="p-2 text-right">{{ format_currency($part->unit_price) }}</td><td class="p-2 text-right font-semibold">{{ format_currency($part->line_total) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex justify-between items-end pt-4 border-t border-border-color text-xs">
            <div class="space-y-1 text-default">
                <div><strong>Customer Approval:</strong> {{ strtoupper($jobCard->customer_approval_status) }}</div>
                <div><strong>QC Status:</strong> {{ strtoupper($jobCard->qc_status) }}</div>
                @if($jobCard->delivered_to)<div><strong>Delivered To:</strong> {{ $jobCard->delivered_to }}</div>@endif
            </div>
            <div class="text-right space-y-1 text-sm">
                <div><span class="text-default">Tax:</span> {{ format_currency($jobCard->tax_amount) }}</div>
                <div class="text-base font-bold text-title"><span>Total:</span> {{ format_currency($jobCard->total_amount ?: $jobCard->estimated_cost) }}</div>
            </div>
        </div>

        @if ($jobCard->images->isNotEmpty())
            <div class="mt-6 pt-4 border-t border-border-color">
                <h3 class="text-xs font-bold uppercase text-default mb-2">Attached Inspection Photos</h3>
                <div class="grid grid-cols-4 gap-2">
                    @foreach ($jobCard->images as $img)
                        <div class="border border-border-color rounded p-1">
                            <img src="{{ $img->url }}" alt="{{ $img->caption ?? 'Photo' }}" class="w-full h-20 object-cover rounded">
                            <div class="text-[10px] text-default truncate mt-0.5">{{ ucfirst($img->stage) }} {{ $img->caption ? '· '.$img->caption : '' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
