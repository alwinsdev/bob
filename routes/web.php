<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Reconciliation\DashboardController;
use App\Http\Controllers\Reconciliation\HomeController;
use App\Http\Controllers\Reconciliation\UploadController;
use App\Http\Controllers\Reconciliation\BatchResultsController;
use App\Http\Controllers\Reconciliation\BatchStatusController;
use App\Http\Controllers\Reconciliation\RecordController;
use App\Http\Controllers\Reconciliation\LockListController;
use App\Http\Controllers\Reconciliation\SettingsController;
use App\Http\Controllers\Reconciliation\AccessControlController;
use App\Http\Controllers\Reconciliation\ContractPatchController;
use App\Http\Controllers\Reconciliation\AuditLogController;
use App\Http\Controllers\Reconciliation\CommissionReportingController;
use App\Http\Controllers\Reconciliation\ContractPatchReportingController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', function () {
        return redirect()->route('reconciliation.home');
    })->name('dashboard');

    // ── Reconciliation Module ────────────────────────────────────────────────
    // Base throttle applied at the group level.
    // Route middleware remains the coarse first gate.
    // Sensitive controllers re-check authorization for defense in depth.
    Route::prefix('reconciliation')
        ->name('reconciliation.')
        ->middleware('throttle:60,1')
        ->group(function () {

            // ── Executive Home ───────────────────────────────────────────────
            Route::get('/home', [HomeController::class, 'index'])
                ->name('home')
                ->middleware('permission:reconciliation.view');

            // ── Reconciliation Dashboard & Grid ──────────────────────────────
            Route::get('/', [DashboardController::class, 'index'])
                ->name('dashboard')
                ->middleware('permission:reconciliation.view');

            Route::get('/data', [DashboardController::class, 'data'])
                ->name('data')
                ->middleware(['throttle:30,1', 'permission:reconciliation.view']);

            Route::get('/export', [DashboardController::class, 'export'])
                ->name('export')
                ->middleware(['throttle:10,1', 'permission:reconciliation.export.download']);

            // ── Import / ETL Runs ────────────────────────────────────────────
            Route::get('/upload', [UploadController::class, 'index'])
                ->name('upload.index')
                ->middleware('permission:reconciliation.etl.run');

            Route::post('/upload', [UploadController::class, 'store'])
                ->name('upload.store')
                ->middleware(['throttle:5,1', 'permission:reconciliation.etl.run']);

            Route::post('/batches/{batch}/rerun', [UploadController::class, 'rerun'])
                ->name('batches.rerun')
                ->middleware(['throttle:5,1', 'permission:reconciliation.reanalysis.run']);

            Route::get('/batches/status', [BatchStatusController::class, 'status'])
                ->name('batches.status')
                ->middleware(['throttle:30,1', 'permission:reconciliation.view']);

            Route::get('/batches/{batch}/download', [UploadController::class, 'download'])
                ->name('batches.download')
                ->middleware('permission:reconciliation.export.download');

            Route::delete('/batches/{batch}', [UploadController::class, 'destroy'])
                ->name('batches.destroy')
                ->middleware('permission:reconciliation.delete');

            // ── Contract Patch Runs ──────────────────────────────────────────
            Route::post('/contract-patch', [ContractPatchController::class, 'store'])
                ->name('contract-patch.store')
                ->middleware(['throttle:10,1', 'permission:reconciliation.etl.run']);

            Route::get('/contract-patch/{batch}/download', [ContractPatchController::class, 'download'])
                ->name('contract-patch.download')
                ->middleware('permission:reconciliation.export.download');

            Route::delete('/contract-patch/{batch}', [ContractPatchController::class, 'destroy'])
                ->name('contract-patch.destroy')
                ->middleware('permission:reconciliation.delete');

            // ── Record Actions ───────────────────────────────────────────────
            Route::post('/records/{record}/lock', [RecordController::class, 'lock'])
                ->name('records.lock')
                ->middleware('permission:reconciliation.edit');

            Route::post('/records/{record}/unlock', [RecordController::class, 'unlock'])
                ->name('records.unlock')
                ->middleware('permission:reconciliation.edit');

            Route::post('/records/{record}/resolve', [RecordController::class, 'resolve'])
                ->name('records.resolve')
                ->middleware('permission:reconciliation.edit');

            Route::post('/records/{record}/flag', [RecordController::class, 'flag'])
                ->name('records.flag')
                ->middleware('permission:reconciliation.edit');

            // ── Bulk Actions ─────────────────────────────────────────────────
            Route::post('/records/bulk-resolve', [RecordController::class, 'bulkResolve'])
                ->name('records.bulk-resolve')
                ->middleware(['throttle:3,1', 'permission:reconciliation.bulk_approve']);

            Route::post('/records/bulk-promote-to-locklist', [RecordController::class, 'bulkPromoteToLocklist'])
                ->name('records.bulk-promote-to-locklist')
                ->middleware(['throttle:3,1', 'permission:reconciliation.bulk_approve']);

            Route::post('/records/{record}/promote-to-locklist', [RecordController::class, 'promoteToLocklist'])
                ->name('records.promote-to-locklist')
                ->middleware('permission:reconciliation.bulk_approve');

            // ── Audit Logs ───────────────────────────────────────────────────
            Route::get('/audit-logs', [AuditLogController::class, 'index'])
                ->name('audit-logs')
                ->middleware('permission:reconciliation.bulk_approve');

            Route::get('/audit-logs/data', [AuditLogController::class, 'data'])
                ->name('audit-logs.data')
                ->middleware(['throttle:30,1', 'permission:reconciliation.bulk_approve']);

            // ── Lock List Manager ────────────────────────────────────────────
            Route::get('/locklist', [LockListController::class, 'index'])
                ->name('locklist.index')
                ->middleware('permission:reconciliation.view');

            Route::get('/locklist/data', [LockListController::class, 'data'])
                ->name('locklist.data')
                ->middleware('permission:reconciliation.view');

            Route::get('/locklist/export', [LockListController::class, 'export'])
                ->name('locklist.export')
                ->middleware(['throttle:10,1', 'permission:reconciliation.export.download']);

            Route::post('/locklist', [LockListController::class, 'store'])
                ->name('locklist.store')
                ->middleware('permission:reconciliation.bulk_approve');

            Route::post('/locklist/import', [LockListController::class, 'import'])
                ->name('locklist.import')
                ->middleware(['throttle:2,1', 'permission:reconciliation.bulk_approve']);

            Route::put('/locklist/{lockList}', [LockListController::class, 'update'])
                ->name('locklist.update')
                ->middleware('permission:reconciliation.bulk_approve');

            Route::delete('/locklist/{lockList}', [LockListController::class, 'destroy'])
                ->name('locklist.destroy')
                ->middleware('permission:reconciliation.delete');

            // ── Batch Results ────────────────────────────────────────────────
            Route::get('/batches/{batch}/results', [BatchResultsController::class, 'show'])
                ->name('batches.show')
                ->middleware('permission:reconciliation.results.view');

            Route::get('/batches/{batch}/results/data', [BatchResultsController::class, 'batchData'])
                ->name('batches.results.data')
                ->middleware(['throttle:30,1', 'permission:reconciliation.results.view']);

            // ── Commission Layer & Reporting ─────────────────────────────────
            Route::get('/commission-dashboard', [CommissionReportingController::class, 'dashboard'])
                ->name('reporting.dashboard')
                ->middleware('permission:reconciliation.results.view');

            Route::get('/final-bob', [CommissionReportingController::class, 'finalBob'])
                ->name('reporting.final-bob')
                ->middleware('permission:reconciliation.results.view');

            Route::get('/final-bob/data', [CommissionReportingController::class, 'finalBobData'])
                ->name('reporting.final-bob.data')
                ->middleware(['throttle:30,1', 'permission:reconciliation.results.view']);

            Route::get('/final-bob/export', [CommissionReportingController::class, 'exportFinalBob'])
                ->name('reporting.final-bob.export')
                ->middleware(['throttle:10,1', 'permission:reconciliation.export.download']);

            Route::get('/contract-patch-ledger', [ContractPatchReportingController::class, 'index'])
                ->name('reporting.contract-patches')
                ->middleware('permission:reconciliation.results.view');

            Route::get('/contract-patch-ledger/data', [ContractPatchReportingController::class, 'data'])
                ->name('reporting.contract-patches.data')
                // 60/min: AG Grid + status/source filter chips + page-size changes
                // can fire 4-6 requests per analyst interaction; 30/min was too tight.
                ->middleware(['throttle:60,1', 'permission:reconciliation.results.view']);

            Route::get('/locklist-impact', [CommissionReportingController::class, 'locklistImpact'])
                ->name('reporting.locklist-impact')
                ->middleware('permission:reconciliation.results.view');

            Route::get('/locklist-impact/data', [CommissionReportingController::class, 'locklistImpactData'])
                ->name('reporting.locklist-impact.data')
                ->middleware(['throttle:30,1', 'permission:reconciliation.results.view']);

            Route::get('/locklist-impact/export', [CommissionReportingController::class, 'exportLocklistImpact'])
                ->name('reporting.locklist-impact.export')
                ->middleware(['throttle:10,1', 'permission:reconciliation.export.download']);

            // ── User Settings ────────────────────────────────────────────────
            Route::get('/settings', [SettingsController::class, 'index'])
                ->name('settings')
                ->middleware('permission:reconciliation.view');

            Route::put('/settings/preferences', [SettingsController::class, 'update'])
                ->name('settings.update')
                ->middleware('permission:reconciliation.view');

            // ── Access Control — identity & authorization governance ──────────
            // Restricted exclusively to super_admin via the access.manage permission.
            Route::get('/access-control', [AccessControlController::class, 'index'])
                ->name('access-control.index')
                ->middleware('permission:access.manage');

            Route::post('/access-control/roles', [AccessControlController::class, 'storeRole'])
                ->name('access-control.roles.store')
                ->middleware('permission:access.manage');

            Route::put('/access-control/roles/{role}/permissions', [AccessControlController::class, 'syncRolePermissions'])
                ->name('access-control.roles.permissions')
                ->middleware('permission:access.manage');

            Route::put('/access-control/users/{user}/roles', [AccessControlController::class, 'syncUserRoles'])
                ->name('access-control.users.roles')
                ->middleware('permission:access.manage');
        });

    // ── User Profile ─────────────────────────────────────────────────────────
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
