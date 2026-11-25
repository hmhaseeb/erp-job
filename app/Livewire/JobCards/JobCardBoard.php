<?php

namespace App\Livewire\JobCards;

use App\Models\JobCard;
use App\Models\SalesInvoice;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Job Card Board & Service Reports')]
class JobCardBoard extends Component
{
    public string $mode = 'board'; // board | reports
    public string $fromDate = '';
    public string $toDate = '';

    public function mount(): void
    {
        $this->mode = request()->route()->defaults['mode'] ?? 'board';
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = now()->toDateString();
    }

    public function moveStage(int $jobCardId, string $targetStatus): void
    {
        abort_unless(auth()->user()->hasPermission('job_cards', 'edit'), 403);
        $jc = JobCard::findOrFail($jobCardId);
        $progressMap = [
            'registered' => 15,
            'diagnosed' => 30,
            'awaiting_approval' => 45,
            'in_progress' => 65,
            'quality_check' => 85,
            'completed' => 100,
            'delivered' => 100,
        ];
        $jc->update([
            'status' => $targetStatus,
            'progress_percent' => $progressMap[$targetStatus] ?? $jc->progress_percent,
            'completed_at' => $targetStatus === 'completed' && ! $jc->completed_at ? now() : $jc->completed_at,
        ]);

        $jc->logs()->create([
            'stage' => 'Stage Transition',
            'status' => $targetStatus,
            'notes' => 'Moved on Workshop Board to '.str_replace('_', ' ', $targetStatus),
            'logged_by' => auth()->id(),
        ]);

        $this->dispatch('toast', message: "{$jc->job_card_no} moved to ".str_replace('_', ' ', $targetStatus).".", type: 'success');
    }

    public function exportReportsCsv(): StreamedResponse
    {
        $rows = JobCard::with(['customer', 'technician'])
            ->whereBetween('received_date', [$this->fromDate, $this->toDate])
            ->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Job Card #', 'Received', 'Customer', 'Device / Product', 'Serial #', 'Technician', 'Status', 'QC', 'Total Billing']);
            foreach ($rows as $jc) {
                fputcsv($out, [
                    $jc->job_card_no,
                    $jc->received_date?->toDateString(),
                    $jc->customer?->name,
                    $jc->item_name,
                    $jc->serial_number,
                    $jc->technician?->full_name,
                    $jc->status,
                    $jc->qc_status,
                    $jc->total_amount,
                ]);
            }
            fclose($out);
        }, 'job-card-report.csv');
    }

    public function render()
    {
        $stages = [
            'registered' => 'Registered',
            'diagnosed' => 'Diagnosed',
            'awaiting_approval' => 'Awaiting Approval',
            'in_progress' => 'In Progress',
            'quality_check' => 'Quality Check',
            'completed' => 'Completed',
            'delivered' => 'Delivered',
        ];

        $boardCards = JobCard::with(['customer', 'technician'])->latest('id')->get()->groupBy('status');

        $reportCards = JobCard::with(['customer', 'technician', 'salesInvoice'])
            ->whereBetween('received_date', [$this->fromDate, $this->toDate])
            ->latest('received_date')
            ->get();

        $serviceRevenue = (float) SalesInvoice::where('source', 'job_card')
            ->where('status', '!=', 'cancelled')
            ->whereBetween('invoice_date', [$this->fromDate, $this->toDate])
            ->sum('total_amount');

        $techStats = $reportCards
            ->groupBy(fn ($jc) => $jc->technician?->full_name ?? 'Unassigned')
            ->map(fn ($group, $name) => (object) [
                'tech_name' => $name,
                'total_jobs' => $group->count(),
                'total_billed' => (float) $group->sum('total_amount'),
            ])
            ->values();

        return view('livewire.job-cards.job-card-board', [
            'stages' => $stages,
            'boardCards' => $boardCards,
            'reportCards' => $reportCards,
            'serviceRevenue' => $serviceRevenue,
            'techStats' => $techStats,
        ]);
    }
}
