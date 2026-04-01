# BOB Reconciliation Hub

Enterprise reconciliation platform built with Laravel for comparing carrier feed data against IMS records, routing exceptions to analysts, and preserving a full audit trail of decisions.

## Contents

- [Overview](#overview)
- [Core Features](#core-features)
- [Architecture](#architecture)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Requirements](#requirements)
- [Quick Start](#quick-start)
- [Local Development](#local-development)
- [Queue and Background Jobs](#queue-and-background-jobs)
- [Seed Data and Demo Accounts](#seed-data-and-demo-accounts)
- [Permissions and Access Control](#permissions-and-access-control)
- [HTTP Routes](#http-routes)
- [Testing](#testing)
- [Troubleshooting](#troubleshooting)
- [Security Notes](#security-notes)
- [License](#license)

## Overview

BOB Reconciliation Hub supports an operations workflow where users:

1. Upload carrier or IMS files.
2. Process and normalize imported rows in background jobs.
3. Run fuzzy matching to determine candidate alignment confidence.
4. Resolve or flag exceptions from a reconciliation grid UI.
5. Track every decision in audit logs.

The application includes role-based access control, record locking, partial failure handling for imports, and archival support for aged resolved records.

## Core Features

- Reconciliation workspace with AG Grid (server-side pagination, sorting, filtering).
- File import flow with batch tracking (`pending`, `processing`, `completed`, `completed_with_errors`, `failed`).
- Row-level import error capture in `import_row_errors`.
- Fuzzy matching service against active IMS agents.
- Record lock/unlock workflow to prevent concurrent analyst edits.
- Single-record and bulk resolve actions with validation.
- Flag workflow for exception escalation.
- Audit trail in `reconciliation_audit_logs`.
- Structured system error logging in `system_error_logs`.
- Archival service for resolved records older than a threshold.

## Architecture

High-level processing flow:

```text
Upload file -> import_batches row created -> ProcessImportBatchJob dispatched
-> ReconciliationETLService parses rows in chunks
-> transform + fuzzy match + insert reconciliation_queue
-> import_row_errors for failed rows
-> analyst reviews records in grid
-> lock -> resolve/flag -> audit log entries
```

## Tech Stack

- Backend: Laravel 12, PHP 8.2+
- Frontend: Blade, Alpine.js, Tailwind CSS, Vite
- Grid: AG Grid Community (CDN)
- File ingestion: Spatie SimpleExcel, Maatwebsite Excel
- Authorization: spatie/laravel-permission
- Queue backend: Laravel database queue driver
- Database: SQLite by default (configurable)

## Project Structure

```text
app/
	Http/
		Controllers/Reconciliation/
		Requests/
	Jobs/
	Models/
	Policies/
	Services/
database/
	migrations/
	seeders/
resources/
	views/reconciliation/
	js/reconciliation.js
	css/app.css
routes/
	web.php
	auth.php
```

## Requirements

- PHP 8.2 or newer
- Composer
- Node.js 18+ and npm
- A supported database (SQLite default, MySQL/PostgreSQL optional)

## Quick Start

From repository root:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm install
npm run build
```

Or run the project bootstrap script defined in `composer.json`:

```bash
composer run setup
php artisan db:seed
```

Then run app + worker (separate terminals):

```bash
php artisan serve
php artisan queue:work
```

Open: `http://127.0.0.1:8000`

Health check endpoint: `GET /up`

Windows PowerShell copy command alternative:

```powershell
Copy-Item .env.example .env
```

## Local Development

Use the bundled concurrent development script:

```bash
composer dev
```

This starts:

- Laravel server
- Queue listener
- Laravel pail logs
- Vite dev server

Or run individually:

```bash
php artisan serve
php artisan queue:listen --tries=1 --timeout=0
npm run dev
```

## Queue and Background Jobs

Queue connection defaults to `database` (see `config/queue.php` and `.env.example`).

Important jobs:

- `ProcessImportBatchJob`: entrypoint for file processing
- `ReleaseExpiredLocksJob`: clears stale record locks
- `ArchiveResolvedRecordsJob`: archives aged resolved records

If queue workers are not running, uploads remain in pending/processing states.

Note: lock-release and archival jobs exist, but no scheduler entries are registered in `routes/console.php` yet. Trigger those jobs manually or add scheduler registrations if you want automatic execution.

## Seed Data and Demo Accounts

`DatabaseSeeder` runs:

- `RolesAndPermissionsSeeder`
- `AgentSeeder`
- `DemoDataSeeder`

Demo users created by seeder:

- Analyst: `analyst@bob.test` / `password`
- Manager: `manager@bob.test` / `password`

## Permissions and Access Control

Permissions configured through Spatie package:

- `reconciliation.view`
- `reconciliation.edit`
- `reconciliation.bulk_approve`
- `import.upload`

Default roles:

- `Reconciliation_Analyst`
- `Manager`

Policies enforce access for upload, view, lock, resolve, and bulk actions.

## HTTP Routes

Primary authenticated routes (`routes/web.php`):

- `GET /reconciliation` -> dashboard page
- `GET /reconciliation/data` -> grid data JSON
- `GET /reconciliation/upload` -> upload UI
- `POST /reconciliation/upload` -> submit import
- `POST /reconciliation/records/{record}/lock`
- `POST /reconciliation/records/{record}/unlock`
- `POST /reconciliation/records/{record}/resolve`
- `POST /reconciliation/records/{record}/flag`
- `POST /reconciliation/records/bulk-resolve`

Auth routes are provided via Laravel Breeze defaults (`routes/auth.php`).

## Testing

Run tests:

```bash
php artisan test
```

`phpunit.xml` is configured to run with in-memory SQLite for tests.

## Troubleshooting

- Import not progressing:
	- Ensure queue worker is running (`php artisan queue:work`).
- Unauthorized (403) on actions:
	- Verify user role/permissions from Spatie permission tables.
- Empty reconciliation grid on fresh setup:
	- Run `php artisan db:seed` to load demo data.
- Frontend changes not visible:
	- Run `npm run dev` for HMR or `npm run build` for production assets.

## Security Notes

- PII fields are encrypted at rest via model casts:
	- `member_dob`
	- `member_phone`
- Reconciliation actions are permission-gated and audited.
- System-level errors are persisted in `system_error_logs` and application logs.

## License

This project is distributed under the MIT License unless your organization policy defines otherwise.
