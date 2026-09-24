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

        $rows = $report->rows($report->handle($aiSystem));

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                return;
            }

            /*
             * The byte order mark makes spreadsheet tools such as Excel read
             * the file as UTF-8, keeping accented characters intact.
             */
            fwrite($output, "\u{FEFF}");

            fputcsv($output, CompileTraceabilityReport::CSV_HEADER, ';', escape: '');

            foreach ($rows as $row) {
                fputcsv($output, $row, ';', escape: '');
            }

            fclose($output);
        }, $this->filename($aiSystem, 'csv'), ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Build the download file name.
     */
    protected function filename(AiSystem $aiSystem, string $extension): string
    {
        return "traceability-report-{$aiSystem->id}-".now()->toDateString().".{$extension}";
    }
}
