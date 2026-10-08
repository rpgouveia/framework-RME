<?php

use App\Http\Controllers\AdverseEventController;
use App\Http\Controllers\AiSystemController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\LinkController;
use App\Http\Controllers\LinkVerificationController;
use App\Http\Controllers\MitigationController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\ReassessmentController;
use App\Http\Controllers\RiskController;
use App\Http\Controllers\StatusHistoryController;
use App\Http\Controllers\SystemChangeController;
use App\Http\Controllers\TraceabilityReportController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('ai-systems', AiSystemController::class);
    // Changes of a system are append only (0021): they explain the
    // reversals they caused.
    Route::resource('ai-systems.system-changes', SystemChangeController::class)
        ->shallow()
        ->only(['index', 'create', 'store', 'show']);
    Route::resource('risks', RiskController::class);
    // Adverse events are append only: they record what happened and, through
    // reassessment, explain status changes on the system's links. Changing
    // the system or date later would leave those changes unexplained.
    Route::resource('adverse-events', AdverseEventController::class)
        ->only(['index', 'create', 'store', 'show']);
    // The catalogue (C2) is only consulted (UC004): mitigations come from the
    // curated data file loaded by the seeder, never from a form (R-8).
    Route::resource('mitigations', MitigationController::class)->only(['index', 'show']);
    Route::resource('owners', OwnerController::class);
    // Used owners are never deleted or edited; they are retired instead.
    Route::post('owners/{owner}/deactivate', [OwnerController::class, 'deactivate'])->name('owners.deactivate');
    Route::post('owners/{owner}/reactivate', [OwnerController::class, 'reactivate'])->name('owners.reactivate');
    // Links are permanent: they are closed by cancelling them, not deleted.
    Route::resource('links', LinkController::class)->except('destroy');
    // Verification (0018): verified on evidence, reverted by hand with a
    // reason. Both are recorded in the status trail.
    Route::post('links/{link}/verification', [LinkVerificationController::class, 'store'])->name('links.verification.store');
    Route::delete('links/{link}/verification', [LinkVerificationController::class, 'destroy'])->name('links.verification.destroy');

    /*
     * Evidence and status history only exist in the context of a link, so they
     * are created and listed through their parent and edited on their own.
     */
    // Evidence is append only: it backs a link's verification (RF03), so it
    // is never edited or deleted. A mistake is corrected by registering more.
    Route::resource('links.evidence', EvidenceController::class)
        ->shallow()
        ->only(['index', 'create', 'store', 'show']);
    // The status trail is append only: entries are never edited or deleted.
    Route::resource('links.status-histories', StatusHistoryController::class)
        ->shallow()
        ->only(['index', 'create', 'store', 'show']);
    // Reassessments are append only (0020): each one concludes a reversal.
    Route::resource('links.reassessments', ReassessmentController::class)
        ->shallow()
        ->only(['index', 'create', 'store', 'show']);

    Route::get('ai-systems/{ai_system}/report.json', [TraceabilityReportController::class, 'json'])
        ->name('ai-systems.report.json');
    Route::get('ai-systems/{ai_system}/report.csv', [TraceabilityReportController::class, 'csv'])
        ->name('ai-systems.report.csv');
    // The system's adverse events, one row per event (0020).
    Route::get('ai-systems/{ai_system}/adverse-events.csv', [TraceabilityReportController::class, 'adverseEventsCsv'])
        ->name('ai-systems.report.adverse-events');
    // The system's changes, one row per change (0021).
    Route::get('ai-systems/{ai_system}/system-changes.csv', [TraceabilityReportController::class, 'systemChangesCsv'])
        ->name('ai-systems.report.system-changes');
});

require __DIR__.'/settings.php';
