<?php

namespace App\Jobs;

use App\Models\Member;
use App\Services\ReportingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateLargeReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour timeout

    public $maxExceptions = 3;

    private string $reportType;

    private array $filters;

    private int $userId;

    private string $userEmail;

    public function __construct(string $reportType, array $filters, int $userId, string $userEmail)
    {
        $this->reportType = $reportType;
        $this->filters = $filters;
        $this->userId = $userId;
        $this->userEmail = $userEmail;

        // Set queue priority based on report size
        $this->onQueue($this->determineQueue());
    }

    public function handle(ReportingService $reportingService): void
    {
        try {
            Log::info('Starting large report generation', [
                'report_type' => $this->reportType,
                'user_id' => $this->userId,
                'filters' => $this->filters,
            ]);

            // Generate the report
            $fileName = $reportingService->generateDownloadableReport($this->reportType, $this->filters);

            // Store report metadata
            $this->storeReportMetadata($fileName);

            // Send completion notification
            $this->sendCompletionNotification($fileName);

            Log::info('Large report generation completed successfully', [
                'report_type' => $this->reportType,
                'file_name' => $fileName,
                'user_id' => $this->userId,
            ]);

        } catch (\Exception $e) {
            Log::error('Large report generation failed', [
                'report_type' => $this->reportType,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->sendFailureNotification($e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Large report generation job failed permanently', [
            'report_type' => $this->reportType,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);

        $this->sendFailureNotification($exception->getMessage());
    }

    private function determineQueue(): string
    {
        // Estimate complexity based on filters
        $estimatedSize = $this->estimateReportSize();

        return match (true) {
            $estimatedSize > 10000 => 'large-reports',
            $estimatedSize > 1000 => 'medium-reports',
            default => 'small-reports'
        };
    }

    private function estimateReportSize(): int
    {
        // Quick estimate without running the full query
        $baseQuery = Member::query();

        if (! empty($this->filters['local_church'])) {
            $baseQuery->where('local_church', $this->filters['local_church']);
        }

        if (! empty($this->filters['church_group'])) {
            $baseQuery->where('church_group', $this->filters['church_group']);
        }

        return $baseQuery->count();
    }

    private function storeReportMetadata(string $fileName): void
    {
        $metadata = [
            'file_name' => $fileName,
            'report_type' => $this->reportType,
            'filters' => $this->filters,
            'user_id' => $this->userId,
            'generated_at' => now()->toISOString(),
            'file_size' => Storage::disk('public')->size($fileName),
            'expires_at' => now()->addDays(7)->toISOString(), // Keep for 7 days
        ];

        Storage::disk('public')->put(
            str_replace('.xlsx', '.json', $fileName),
            json_encode($metadata, JSON_PRETTY_PRINT)
        );
    }

    private function sendCompletionNotification(string $fileName): void
    {
        // Send email notification (implement your email class)
        $downloadUrl = url("storage/{$fileName}");

        // Log for now, implement email later
        Log::info('Report ready for download', [
            'user_email' => $this->userEmail,
            'download_url' => $downloadUrl,
            'file_name' => $fileName,
        ]);
    }

    private function sendFailureNotification(string $error): void
    {
        Log::error('Sending failure notification', [
            'user_email' => $this->userEmail,
            'error' => $error,
        ]);
    }
}
