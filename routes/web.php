<?php
// routes/web.php
use App\Http\Controllers\CaseMarkController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/casemark');
});

// Case Mark Routes
Route::prefix('casemark')->name('casemark.')->group(function () {

    // Dashboard
    Route::get('/', [CaseMarkController::class, 'index'])->name('dashboard');

    // Content List
    Route::get('/content-list/{case_no?}', [CaseMarkController::class, 'contentList'])->name('content-list');

    // History
    Route::get('/history', [CaseMarkController::class, 'history'])->name('history');
    Route::get('/history/{case_no}', [CaseMarkController::class, 'historyDetail'])->name('history.detail');

    // Upload Excel
    Route::get('/upload', [CaseMarkController::class, 'upload'])->name('upload');
    Route::post('/upload', [CaseMarkController::class, 'uploadExcel'])->name('upload.excel');

    // List Case Mark
    Route::get('/list', [CaseMarkController::class, 'listCaseMark'])->name('list');
    Route::get('/list/{case_no}', [CaseMarkController::class, 'listCaseMarkDetail'])->name('list.detail');

    Route::post('/scan', [CaseMarkController::class, 'processScan'])->name('scan.process');
    Route::post('/mark-packed', [CaseMarkController::class, 'markAsPacked'])->name('mark.packed');
});

// API Routes for AJAX calls
Route::prefix('api/casemark')->name('api.casemark.')->group(function () {
    Route::post('/scan', [CaseMarkController::class, 'processScan'])->name('scan');
    Route::post('/mark-packed', [CaseMarkController::class, 'markAsPacked'])->name('mark.packed');
});
