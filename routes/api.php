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

        // New scanning endpoints
        Route::post('/scan-container', [CaseMarkApiController::class, 'scanContainer'])->name('scan.container');
        Route::post('/scan-box', [CaseMarkApiController::class, 'scanBox'])->name('scan.box');
        // Final barcode scan for submit
        Route::post('/scan-final-barcode', [CaseMarkApiController::class, 'scanFinalBarcode'])->name('scan.final.barcode');
        Route::post('/submit-case', [CaseMarkApiController::class, 'submitCase'])->name('submit.case');
        Route::get('/get-case-progress/{caseId}', [CaseMarkApiController::class, 'getCaseProgress'])->name('get.case.progress');

        // Excel preview
        Route::post('/preview-excel', [CaseMarkApiController::class, 'previewExcel'])->name('preview.excel');
    });
});
