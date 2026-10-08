<?php

namespace App\Http\Controllers;

use App\Actions\CompileTraceabilityReport;
use App\Models\AiSystem;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export the traceability report of an AI system in open formats.
 */
class TraceabilityReportController extends Controller
{
    /**
     * Download the report as JSON.
     */
    public function json(AiSystem $aiSystem, CompileTraceabilityReport $report): JsonResponse
    {
        Gate::authorize('view', $aiSystem);

        return response()
            ->json($report->handle($aiSystem), options: JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ->header('Content-Disposition', 'attachment; filename="'.$this->filename($aiSystem, 'json').'"');
    }

    /**
     * Download the report as CSV, one row per link.
     */
    public function csv(AiSystem $aiSystem, CompileTraceabilityReport $report): StreamedResponse
    {
        Gate::authorize('view', $aiSystem);

        return $this->streamCsv(
            CompileTraceabilityReport::CSV_HEADER,
            $report->rows($report->handle($aiSystem)),
            $this->filename($aiSystem, 'csv'),
        );
    }

    /**
     * Download the system's adverse events as CSV, one row per event, also
     * those that reverted no link (0020).
     */
    public function adverseEventsCsv(AiSystem $aiSystem, CompileTraceabilityReport $report): StreamedResponse
    {
        Gate::authorize('view', $aiSystem);

        return $this->streamCsv(
            CompileTraceabilityReport::ADVERSE_EVENT_CSV_HEADER,
            $report->adverseEventRows($aiSystem),
            "adverse-events-{$aiSystem->id}-".now()->toDateString().'.csv',
        );
    }

    /**
     * Download the system's changes as CSV, one row per change, also those
     * that reverted no link (0021).
     */
    public function systemChangesCsv(AiSystem $aiSystem, CompileTraceabilityReport $report): StreamedResponse
    {
        Gate::authorize('view', $aiSystem);

        return $this->streamCsv(
            CompileTraceabilityReport::SYSTEM_CHANGE_CSV_HEADER,
            $report->systemChangeRows($aiSystem),
            "system-changes-{$aiSystem->id}-".now()->toDateString().'.csv',
        );
    }

    /**
     * Stream rows as a CSV download for spreadsheet tools.
     *
     * @param  list<string>  $header
     * @param  array<int, list<string|int|null>>  $rows
     */
    protected function streamCsv(array $header, array $rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                return;
            }

            /*
             * The byte order mark makes spreadsheet tools such as Excel read
             * the file as UTF-8, keeping accented characters intact.
             */
            fwrite($output, "\u{FEFF}");

            fputcsv($output, $header, ';', escape: '');

            foreach ($rows as $row) {
                fputcsv($output, $row, ';', escape: '');
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Build the download file name.
     */
    protected function filename(AiSystem $aiSystem, string $extension): string
    {
        return "traceability-report-{$aiSystem->id}-".now()->toDateString().".{$extension}";
    }
}
