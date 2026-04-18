<?php

namespace App\Jobs;

use App\Imports\AdvancedProductsImport;
use App\Models\ImportRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProcessImportRunJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 1200; // 20 минут

    public function __construct(public readonly int $importRunId)
    {
        $this->onQueue('imports');
    }

    public function handle(): void
    {
        $run = ImportRun::find($this->importRunId);
        if (! $run) {
            return;
        }

        $run->update([
            'status' => 'processing',
            'started_at' => now(),
            'error_message' => null,
        ]);

        try {
            if (! $run->file_path || ! Storage::disk('local')->exists($run->file_path)) {
                throw new \RuntimeException('Файл импорта не найден');
            }

            $import = new AdvancedProductsImport(
                $run->shop_id,
                (array) $run->mapping,
                (int) $run->available_slots_before_import,
                (string) ($run->image_base_url ?? '')
            );

            Excel::import($import, Storage::disk('local')->path($run->file_path));

            $failures = $import->getFailures();
            $successCount = $import->getSuccessCount();
            $totalRows = $import->getRowCount();
            $skippedDueToLimit = $import->getSkippedDueToLimit();

            $run->update([
                'status' => 'completed',
                'total_rows' => $totalRows,
                'imported_count' => $successCount,
                'success_count' => $successCount,
                'failed_count' => count($failures),
                'skipped_due_to_limit' => $skippedDueToLimit,
                'failures' => collect($failures)->take(100)->map(function ($failure) {
                    return [
                        'row' => $failure->row(),
                        'attribute' => $failure->attribute(),
                        'errors' => $failure->errors(),
                        'values' => $failure->values(),
                    ];
                })->values()->all(),
                'finished_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $run->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            throw $e;
        } finally {
            if ($run->file_path && Storage::disk('local')->exists($run->file_path)) {
                Storage::disk('local')->delete($run->file_path);
            }
        }
    }
}
