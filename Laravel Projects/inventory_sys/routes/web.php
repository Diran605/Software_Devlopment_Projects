<?php

use Illuminate\Support\Facades\Route;

// Redirect root to the branch operations panel
Route::get('/', function () {
    // return view('welcome');
    return redirect('/app');
});

// Printable sales order receipt
Route::get('/receipts/sales/{order}', \App\Http\Controllers\ReceiptController::class)
    ->name('receipts.sales')
    ->middleware(['auth']);

// Import template download routes
Route::prefix('import-templates')->middleware(['auth'])->group(function () {
    Route::get('/items', [\App\Http\Controllers\ImportTemplateController::class, 'items'])->name('import-templates.items');
    Route::get('/opening-stock', [\App\Http\Controllers\ImportTemplateController::class, 'openingStock'])->name('import-templates.opening-stock');
});

// Report PDF routes
Route::prefix('reports')->middleware(['auth'])->group(function () {
    Route::get('/sales/pdf', [\App\Http\Controllers\ReportPdfController::class, 'sales'])->name('reports.sales.pdf');
    Route::get('/stock-valuation/pdf', [\App\Http\Controllers\ReportPdfController::class, 'stockValuation'])->name('reports.stock-valuation.pdf');
    Route::get('/purchases/pdf', [\App\Http\Controllers\ReportPdfController::class, 'purchases'])->name('reports.purchases.pdf');
    Route::get('/profit-loss/pdf', [\App\Http\Controllers\ReportPdfController::class, 'profitLoss'])->name('reports.profit-loss.pdf');
    Route::get('/expiry/pdf', [\App\Http\Controllers\ReportPdfController::class, 'expiry'])->name('reports.expiry.pdf');
});

// Simple GET route to clear session/logout
Route::get('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/admin/login');
})->name('logout');
