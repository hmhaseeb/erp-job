<?php

namespace App\Livewire\Users;

use App\Models\ActivityLog;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('User Activity Logs')]
class ActivityLogViewer extends Component
{
    use WithPagination;

    public string $search = '';
    public string $moduleFilter = '';
    public string $userFilter = '';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingModuleFilter(): void { $this->resetPage(); }
    public function updatingUserFilter(): void { $this->resetPage(); }

    public function exportCsv(): StreamedResponse
    {
        $logs = ActivityLog::with('user')->latest()->limit(500)->get();

        return response()->streamDownload(function () use ($logs) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Timestamp', 'User', 'Module', 'Action', 'Description', 'IP Address']);
            foreach ($logs as $log) {
                fputcsv($out, [
                    $log->created_at?->toDateTimeString(),
                    $log->user?->name ?? 'System',
                    $log->module,
                    $log->action,
                    $log->description,
                    $log->ip_address,
                ]);
            }
            fclose($out);
        }, 'activity-logs.csv');
    }

    public function render()
    {
        $logs = ActivityLog::with('user')
            ->when($this->search, fn ($q) => $q->where(fn ($sq) => $sq->where('description', 'like', "%{$this->search}%")->orWhere('action', 'like', "%{$this->search}%")))
            ->when($this->moduleFilter, fn ($q) => $q->where('module', $this->moduleFilter))
            ->when($this->userFilter, fn ($q) => $q->where('user_id', $this->userFilter))
            ->latest()
            ->paginate(15);

        $modules = ActivityLog::select('module')->distinct()->pluck('module');

        return view('livewire.users.activity-log-viewer', [
            'logs' => $logs,
            'modules' => $modules,
            'users' => User::orderBy('name')->get(),
        ]);
    }
}
