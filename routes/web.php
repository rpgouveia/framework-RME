<?php

use App\Http\Controllers\AdverseEventController;
use App\Http\Controllers\AiSystemController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\LinkController;
use App\Http\Controllers\MitigationController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\RiskController;
use App\Http\Controllers\StatusHistoryController;
use App\Http\Controllers\TraceabilityReportController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::resource('ai-systems', AiSystemController::class);
    Route::resource('risks', RiskController::class);
    Route::resource('adverse-events', AdverseEventController::class);
    Route::resource('mitigations', MitigationController::class);
    Route::resource('owners', OwnerController::class);
    // Used owners are never deleted or edited; they are retired instead.
    Route::post('owners/{owner}/deactivate', [OwnerController::class, 'deactivate'])->name('owners.deactivate');
    Route::post('owners/{owner}/reactivate', [OwnerController::class, 'reactivate'])->name('owners.reactivate');
    // Links are permanent: they are closed by cancelling them, not deleted.
    Route::resource('links', LinkController::class)->except('destroy');

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

    Route::get('ai-systems/{ai_system}/report.json', [TraceabilityReportController::class, 'json'])
        ->name('ai-systems.report.json');
    Route::get('ai-systems/{ai_system}/report.csv', [TraceabilityReportController::class, 'csv'])
        ->name('ai-systems.report.csv');
});

require __DIR__.'/settings.php';
