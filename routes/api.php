<?php

// routes/api.php
use App\Http\Controllers\Api\CaseMarkApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('api')->group(function () {
    
    // Case Mark API Routes
    Route::prefix('casemark')->name('api.casemark.')->group(function () {
        
        // Scan operations
        Route::post('/scan', [CaseMarkApiController::class, 'processScan'])->name('scan');
        Route::post('/mark-packed', [CaseMarkApiController::class, 'markAsPacked'])->name('mark.packed');
        
        // Excel preview
        Route::post('/preview-excel', [CaseMarkApiController::class, 'previewExcel'])->name('preview.excel');
        
        // Container info
        Route::get('/container-info/{caseNo}', [CaseMarkApiController::class, 'getContainerInfo'])->name('container.info');
        
        // Statistics
        Route::get('/stats', [CaseMarkApiController::class, 'getStats'])->name('stats');
        Route::get('/stats/{caseNo}', [CaseMarkApiController::class, 'getCaseStats'])->name('case.stats');
        
        // Search
        Route::get('/search', [CaseMarkApiController::class, 'search'])->name('search');
        
    });
});