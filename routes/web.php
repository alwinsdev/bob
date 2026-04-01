<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Reconciliation\DashboardController;
use App\Http\Controllers\Reconciliation\UploadController;
use App\Http\Controllers\Reconciliation\RecordController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return redirect()->route('reconciliation.dashboard');
    })->name('dashboard');

    // Reconciliation Routes
    Route::prefix('reconciliation')->name('reconciliation.')->group(function () {
        
        // Dashboard / Grid view
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard')->can('viewAny', App\Models\ReconciliationQueue::class);
        Route::get('/data', [DashboardController::class, 'data'])->name('data')->can('viewAny', App\Models\ReconciliationQueue::class);
        
        // Uploads
        Route::get('/upload', [UploadController::class, 'index'])->name('upload.index')->can('create', App\Models\ImportBatch::class);
        Route::post('/upload', [UploadController::class, 'store'])->name('upload.store')->can('create', App\Models\ImportBatch::class);
        
        // Record Actions
        Route::post('/records/{record}/lock', [RecordController::class, 'lock'])->name('records.lock')->can('lock', 'record');
        Route::post('/records/{record}/unlock', [RecordController::class, 'unlock'])->name('records.unlock');
        Route::post('/records/{record}/resolve', [RecordController::class, 'resolve'])->name('records.resolve')->can('update', 'record');
        Route::post('/records/{record}/flag', [RecordController::class, 'flag'])->name('records.flag')->can('update', 'record');
        
        // Bulk Actions
        Route::post('/records/bulk-resolve', [RecordController::class, 'bulkResolve'])->name('records.bulk-resolve')->can('bulkApprove', App\Models\ReconciliationQueue::class);
        
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
