# BOB Reconciliation Hub

Enterprise reconciliation platform built on Laravel for ingesting carrier/source feeds, running deterministic matching, enabling analyst interventions, and delivering auditable reporting outputs.

## Documentation Hub

- [Developer Architecture Guide](docs/developer-architecture-guide.md)
- [User Operations Guide](docs/user-operations-guide.md)
- [Flow Diagrams](docs/flow-diagrams.md)

Use these three documents as the primary reference set for onboarding, implementation, operations, and support.

## Platform Scope

BOB Reconciliation Hub supports the full reconciliation lifecycle:
- Standard synchronization uploads (Carrier with IMS and/or Health Sherpa, optional Payee)
- Deterministic ETL matching and queue record generation
- Analyst lock/resolve/flag workflows with audit logging
- Lock list governance and policy override behavior
- Contract patch runs for controlled mid-cycle adjustments
- Reporting and exports (Final BOB, Locklist Impact, Contract Patch Ledger)

## Architecture at a Glance

High-level flow:
1. User uploads files from the Import Feeds screen.
2. Import batch metadata is persisted and queue jobs are dispatched.
3. ETL service builds lookup maps and processes rows in streaming mode.
4. Reconciliation queue rows are created with match metadata and status.
5. Analysts review records in the grid and perform lock/resolve/flag actions.
6. Audit logs and patch logs preserve action traceability.
7. Reporting controllers expose operational and commission-ready views.
8. Scheduler handles lock expiry release and record archival.

For full diagrams, see [Flow Diagrams](docs/flow-diagrams.md).

## Core Modules

- Controllers: [app/Http/Controllers/Reconciliation](app/Http/Controllers/Reconciliation)
- Services: [app/Services](app/Services) and [app/Services/Reconciliation](app/Services/Reconciliation)
- Jobs: [app/Jobs](app/Jobs)
- Models: [app/Models](app/Models)
- Policies: [app/Policies](app/Policies)
- Requests: [app/Http/Requests](app/Http/Requests)
- Views: [resources/views/reconciliation](resources/views/reconciliation)
- Frontend scripts: [resources/js/reconciliation.js](resources/js/reconciliation.js), [resources/js/reconciliation-audit.js](resources/js/reconciliation-audit.js)

Route map entrypoint:
- [routes/web.php](routes/web.php)

Scheduler entrypoint:
- [routes/console.php](routes/console.php)

## Technology Stack

- PHP 8.2+
- Laravel 12
- MySQL (default project database)
- Blade + Alpine.js + Tailwind + Vite
- AG Grid (reconciliation/reporting grid surfaces)
- Spatie Permission (RBAC)
- Spatie Simple Excel + Laravel Excel (imports/exports)
- DomPDF (PDF exports)

Dependency manifests:
- [composer.json](composer.json)
- [package.json](package.json)

## Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+ and npm
- MySQL server

### Install and Bootstrap

Run from project root:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm install
npm run build
```

Or use the setup script:

```bash
composer run setup
php artisan db:seed
```

### Run Locally

Single command development stack:

```bash
composer dev
```

Manual terminals:

```bash
php artisan serve
php artisan queue:listen --tries=1 --timeout=0
npm run dev
```

### Scheduler

In development, run scheduler loop when validating maintenance behavior:

```bash
php artisan schedule:work
```

Scheduled actions are defined in [routes/console.php](routes/console.php):
- release expired locks every 30 minutes
- archive resolved records daily at 01:00

## Authorization and Access

Permission keys and role setup are seeded by:
- [database/seeders/RolesAndPermissionsSeeder.php](database/seeders/RolesAndPermissionsSeeder.php)

Policy classes:
- [app/Policies/ImportBatchPolicy.php](app/Policies/ImportBatchPolicy.php)
- [app/Policies/ReconciliationQueuePolicy.php](app/Policies/ReconciliationQueuePolicy.php)
- [app/Policies/LockListPolicy.php](app/Policies/LockListPolicy.php)
- [app/Policies/ContractPatchPolicy.php](app/Policies/ContractPatchPolicy.php)

Gate bootstrap:
- [app/Providers/AppServiceProvider.php](app/Providers/AppServiceProvider.php)

## Testing

Run tests:

```bash
php artisan test
```

Current PHPUnit environment profile:
- DB_CONNECTION = mysql
- DB_DATABASE = bob_test

Reference file:
- [phpunit.xml](phpunit.xml)

## Operational Notes

- Queue worker must be running for batch processing.
- Upload and patch runs are asynchronous and status-driven.
- Reconciliation and patch actions are auditable.
- User preferences (theme, export format, page size) are persisted per user.

## Troubleshooting Quick Checks

- Stuck batch: verify queue worker, batch status, and failed jobs.
- No matches: verify input headers, required identifiers, and source file presence.
- 403 errors: verify assigned permissions and role cache state.
- Missing downloads: confirm output file exists and export permission is granted.

## Additional References

- [Developer Architecture Guide](docs/developer-architecture-guide.md)
- [User Operations Guide](docs/user-operations-guide.md)
- [Flow Diagrams](docs/flow-diagrams.md)

## License

This repository follows the project licensing policy defined by your organization.
