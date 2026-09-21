<?php

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
    Route::resource('mitigations', MitigationController::class);
    Route::resource('owners', OwnerController::class);
    Route::resource('links', LinkController::class);

    /*
     * Evidence and status history only exist in the context of a link, so they
     * are created and listed through their parent and edited on their own.
     */
    Route::resource('links.evidence', EvidenceController::class)->shallow();
    Route::resource('links.status-histories', StatusHistoryController::class)->shallow();

    Route::get('ai-systems/{ai_system}/report.json', [TraceabilityReportController::class, 'json'])
        ->name('ai-systems.report.json');
    Route::get('ai-systems/{ai_system}/report.csv', [TraceabilityReportController::class, 'csv'])
        ->name('ai-systems.report.csv');
});

require __DIR__.'/settings.php';
