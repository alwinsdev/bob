# BOB Reconciliation Hub
## Developer Architecture Guide

Version: 2026-04-08
Audience: Backend developers, frontend developers, QA engineers, DevOps/SRE, technical leads

## 1) Purpose
This guide explains the production architecture of the BOB Reconciliation Hub, including:
- how data enters the system
- how ETL and matching are executed
- how analyst workflows are enforced
- how reporting surfaces are generated
- how authorization, auditability, and operations controls are implemented

For visual flows, see [Flow Diagrams](./flow-diagrams.md).
For operations/user-facing instructions, see [User Operations Guide](./user-operations-guide.md).

## 2) System Summary
BOB Reconciliation Hub is a Laravel 12 application that reconciles carrier records against IMS and Health Sherpa sources, then routes unresolved records to analysts for lock/resolve/flag actions with full auditability.

Primary pipeline:
1. Upload standard synchronization inputs (Carrier plus IMS and/or Health Sherpa, optional Payee).
2. Create an import batch and run asynchronous ETL.
3. Produce reconciliation queue records with deterministic match methods.
4. Allow analyst interventions (lock, resolve, flag, promote to lock list).
5. Generate reporting outputs (Final BOB, Locklist impact, Contract patch ledger).
6. Run scheduled maintenance (expired lock release, archival).

## 3) Core Technical Stack
- Backend: Laravel 12, PHP 8.2+
- Authorization: spatie/laravel-permission
- File parsing: spatie/simple-excel, maatwebsite/excel
- Export formats: XLSX/CSV (maatwebsite/excel), PDF (barryvdh/laravel-dompdf)
- Frontend: Blade, Alpine.js, Tailwind, AG Grid
- Queue: Laravel queue (database driver by default)
- Database: MySQL (current testing profile also targets MySQL)

Dependency sources:
- [composer.json](../composer.json)
- [package.json](../package.json)

## 4) Module Catalog

### 4.1 HTTP Layer (Controllers)
Reconciliation module controllers:
- [UploadController](../app/Http/Controllers/Reconciliation/UploadController.php)
  - standard run creation
  - in-place rerun for standard runs and contract patches
  - output download and batch deletion
- [ContractPatchController](../app/Http/Controllers/Reconciliation/ContractPatchController.php)
  - creates contract patch runs against latest completed standard batch
  - contract patch output download and delete
- [DashboardController](../app/Http/Controllers/Reconciliation/DashboardController.php)
  - dashboard metrics, queue data API, exports
- [RecordController](../app/Http/Controllers/Reconciliation/RecordController.php)
  - lock/unlock, resolve, flag, bulk resolve, promote-to-locklist
- [BatchStatusController](../app/Http/Controllers/Reconciliation/BatchStatusController.php)
  - batch polling endpoint for live UI
- [BatchResultsController](../app/Http/Controllers/Reconciliation/BatchResultsController.php)
  - per-batch results detail endpoint
- [LockListController](../app/Http/Controllers/Reconciliation/LockListController.php)
  - lock list CRUD, import, export
- [CommissionReportingController](../app/Http/Controllers/Reconciliation/CommissionReportingController.php)
  - Final BOB, locklist impact, commission dashboard, exports
- [ContractPatchReportingController](../app/Http/Controllers/Reconciliation/ContractPatchReportingController.php)
  - contract patch ledger and run summaries
- [AuditLogController](../app/Http/Controllers/Reconciliation/AuditLogController.php)
  - unified audit feed from reconciliation and patch logs
- [AccessControlController](../app/Http/Controllers/Reconciliation/AccessControlController.php)
  - role, permission, user-role governance
- [SettingsController](../app/Http/Controllers/Reconciliation/SettingsController.php)
  - user preferences persistence
- [HomeController](../app/Http/Controllers/Reconciliation/HomeController.php)
  - executive landing metrics

### 4.2 Service Layer
Core services:
- [ReconciliationETLService](../app/Services/ReconciliationETLService.php)
  - standard ETL engine
  - contract patch processor
  - file header validation
  - map-building and stream processing
- [ReconciliationService](../app/Services/Reconciliation/ReconciliationService.php)
  - orchestration for start/rerun/delete standard and patch runs
- [ContractPatchService](../app/Services/Reconciliation/ContractPatchService.php)
  - contract patch batch creation/deletion
- [FileUploadService](../app/Services/Reconciliation/FileUploadService.php)
  - storage, safe naming, duplication, cleanup
- [BatchSerializer](../app/Services/Reconciliation/BatchSerializer.php)
  - normalized JSON payload for live upload UI
- [RecordLockService](../app/Services/RecordLockService.php)
  - lock acquisition/release/expiry logic
- [ArchivalService](../app/Services/ArchivalService.php)
  - archival of resolved records older than threshold
- [ErrorLoggerService](../app/Services/ErrorLoggerService.php)
  - structured system error persistence + log forwarding

### 4.3 Queue Jobs
- [ProcessImportBatchJob](../app/Jobs/ProcessImportBatchJob.php)
- [ProcessContractPatchJob](../app/Jobs/ProcessContractPatchJob.php)
- [ReleaseExpiredLocksJob](../app/Jobs/ReleaseExpiredLocksJob.php)
- [ArchiveResolvedRecordsJob](../app/Jobs/ArchiveResolvedRecordsJob.php)

### 4.4 Data Transfer and Domain Support
- DTOs:
  - [UploadBatchDTO](../app/DTOs/Reconciliation/UploadBatchDTO.php)
  - [RetryBatchDTO](../app/DTOs/Reconciliation/RetryBatchDTO.php)
  - [ContractPatchDTO](../app/DTOs/Reconciliation/ContractPatchDTO.php)
- Enum:
  - [SystemRole](../app/Enums/SystemRole.php)
- Events (lightweight payload events):
  - [BatchProcessed](../app/Events/Reconciliation/BatchProcessed.php)
  - [ContractPatchCompleted](../app/Events/Reconciliation/ContractPatchCompleted.php)
- Exceptions:
  - [FileUploadException](../app/Exceptions/Reconciliation/FileUploadException.php)
  - [BatchProcessingException](../app/Exceptions/Reconciliation/BatchProcessingException.php)

### 4.5 Models and Persistence
- [ImportBatch](../app/Models/ImportBatch.php)
- [ReconciliationQueue](../app/Models/ReconciliationQueue.php)
- [ImportRowError](../app/Models/ImportRowError.php)
- [LockList](../app/Models/LockList.php)
- [ContractPatchLog](../app/Models/ContractPatchLog.php)
- [ReconciliationAuditLog](../app/Models/ReconciliationAuditLog.php)
- [SystemErrorLog](../app/Models/SystemErrorLog.php)
- [Agent](../app/Models/Agent.php)
- [User](../app/Models/User.php)

### 4.6 Frontend Modules
- Layout shell:
  - [reconciliation-layout component](../resources/views/components/reconciliation-layout.blade.php)
- Reconciliation screens:
  - [home](../resources/views/reconciliation/home.blade.php)
  - [dashboard](../resources/views/reconciliation/dashboard.blade.php)
  - [upload](../resources/views/reconciliation/upload.blade.php)
  - [batch results](../resources/views/reconciliation/batch-results.blade.php)
  - [lock list](../resources/views/reconciliation/locklist.blade.php)
  - [audit logs](../resources/views/reconciliation/audit-logs.blade.php)
  - [access control](../resources/views/reconciliation/access-control.blade.php)
  - [settings](../resources/views/reconciliation/settings.blade.php)
  - reporting screens under [reconciliation/reporting](../resources/views/reconciliation/reporting)
- Client scripts:
  - [reconciliation.js](../resources/js/reconciliation.js)
  - [reconciliation-audit.js](../resources/js/reconciliation-audit.js)

## 5) Routing and Authorization
Primary routes are declared in [routes/web.php](../routes/web.php).

Key design characteristics:
- All reconciliation routes are under /reconciliation with shared throttle middleware.
- Fine-grained permissions are enforced route-by-route.
- Super admin gate bypass is configured in [AppServiceProvider](../app/Providers/AppServiceProvider.php).

Primary permission set:
- reconciliation.view
- reconciliation.edit
- reconciliation.bulk_approve
- reconciliation.delete
- reconciliation.etl.run
- reconciliation.reanalysis.run
- reconciliation.results.view
- reconciliation.export.download
- access.manage

Role seeding and mapping:
- [RolesAndPermissionsSeeder](../database/seeders/RolesAndPermissionsSeeder.php)

Policies:
- [ImportBatchPolicy](../app/Policies/ImportBatchPolicy.php)
- [ReconciliationQueuePolicy](../app/Policies/ReconciliationQueuePolicy.php)
- [LockListPolicy](../app/Policies/LockListPolicy.php)
- [ContractPatchPolicy](../app/Policies/ContractPatchPolicy.php)

## 6) Data Model and Lifecycles

### 6.1 Import Batch Lifecycle
Status values:
- pending
- processing
- completed
- completed_with_errors
- failed

Batch type values:
- standard
- contract_patch

Important counters:
- total_records, processed_records, failed_records, skipped_records
- ims_matched_records, hs_matched_records, locklist_matched_records
- contract_patched_records

### 6.2 Reconciliation Queue Lifecycle
Status values:
- pending
- matched
- resolved
- flagged
- skipped

Additional state dimensions:
- lock state: locked_by, locked_at
- resolution state: resolved_by, resolved_at
- override metadata: override_flag, override_source
- patch metadata: is_patched
- archival: archived_at

### 6.3 Contract Patch Audit
All applied patch changes are stored in [contract_patch_logs](../app/Models/ContractPatchLog.php), capturing before/after values and operator context.

### 6.4 Row-Level Error Tracking
Parsing or validation failures during ETL are persisted in import_row_errors and surfaced in upload UX summaries.

## 7) ETL and Matching Rules
Implemented by [ReconciliationETLService](../app/Services/ReconciliationETLService.php).

### 7.1 Standard matching stages
1. IMS stage (primary):
   - email exact
   - phone exact
   - first+last exact
   - DOB+last exact
2. Health Sherpa stage (secondary):
   - email exact
   - phone + effective date within ±30 days
3. Lock list stage (final authority):
   - policy/contract exact override

### 7.2 Current field compatibility handling
The ETL engine supports multiple column aliases for practical file template variants, including:
- BOB: MEMBER_PHONE_NUMBER or PHONE
- BOB: MEMBER_EMAIL_ADDRESS or EMAIL
- IMS: DEPARTMENT_NAME or AGENT_NAME
- IMS: AGENT_ID or AGENT_CODE
- Payee map: DEPARTMENT_NAME or AGENT_NAME

### 7.3 Contract patch policy
Contract patch engine runs against parent standard batch and historical context.
Eligibility and behavior are controlled via configuration:
- allow_force_patch in [config/reconciliation.php](../config/reconciliation.php)
- patch_chunk_size and patch_realtime_flush_interval for performance/progress behavior

## 8) Background and Scheduler Operations

### 8.1 Queue-driven operations
- Standard uploads dispatch ProcessImportBatchJob.
- Contract patch uploads dispatch ProcessContractPatchJob.

Queue configuration:
- [config/queue.php](../config/queue.php)
- default connection: database (unless overridden)

### 8.2 Scheduler tasks
Defined in [routes/console.php](../routes/console.php):
- release expired locks every 30 minutes
- archive resolved records daily at 01:00

## 9) Exports and Reporting
Export classes:
- [ReconciliationExport](../app/Exports/ReconciliationExport.php)
- [FinalBobExport](../app/Exports/FinalBobExport.php)
- [LockListExport](../app/Exports/LockListExport.php)
- [LocklistImpactExport](../app/Exports/LocklistImpactExport.php)

Reporting controllers:
- [CommissionReportingController](../app/Http/Controllers/Reconciliation/CommissionReportingController.php)
- [ContractPatchReportingController](../app/Http/Controllers/Reconciliation/ContractPatchReportingController.php)

## 10) Configuration Reference
- Reconciliation behavior: [config/reconciliation.php](../config/reconciliation.php)
- Queue: [config/queue.php](../config/queue.php)
- Permissions package: [config/permission.php](../config/permission.php)
- Database connection defaults: [config/database.php](../config/database.php)

## 11) Security and Compliance Notes
- Role- and permission-based authorization enforced by route middleware and policies.
- Super admin bypass is explicit and centralized in AppServiceProvider.
- Member PII columns in reconciliation_queue use encrypted casts.
- Every critical analyst action is auditable (reconciliation_audit_logs and contract_patch_logs).

## 12) Developer Runbook

### 12.1 Local bootstrap
1. composer install
2. copy .env.example to .env
3. php artisan key:generate
4. php artisan migrate
5. php artisan db:seed
6. npm install
7. npm run build

Or use:
- composer run setup

### 12.2 Start local services
Option A:
- composer dev

Option B (separate terminals):
- php artisan serve
- php artisan queue:listen --tries=1 --timeout=0
- npm run dev

### 12.3 Test execution
- php artisan test

Current test profile in [phpunit.xml](../phpunit.xml):
- DB_CONNECTION=mysql
- DB_DATABASE=bob_test

## 13) Extension Guidelines

### 13.1 Add a new reconciliation source
1. Add source file fields to import_batches and request validation.
2. Extend ReconciliationETLService map-building and stage matching.
3. Update output workbook serialization and reporting if required.
4. Add route permission gating if new screens are introduced.
5. Add feature tests for upload, matching, and export impacts.

### 13.2 Add a new analyst action
1. Add endpoint in RecordController.
2. Add policy/permission mapping.
3. Persist audit record.
4. Update AG Grid action controls and drawer behavior.

### 13.3 Add a new report
1. Add controller action and route with results/export permissions.
2. Add export class if file downloads are required.
3. Add reporting view and API endpoint.
4. Document the new report in user guide and README docs index.

## 14) Known Pitfalls and Migration Notes
- Ensure roles/permissions are reseeded after permission changes.
- Do not assume old import.upload permission exists; canonical permission is reconciliation.etl.run.
- Keep route permissions and sidebar visibility aligned to avoid dead navigation paths.
- If frontend behavior appears stale, rebuild Vite assets and verify public/build output.

## 15) Related Documents
- [User Operations Guide](./user-operations-guide.md)
- [Flow Diagrams](./flow-diagrams.md)
- [README](../README.md)
