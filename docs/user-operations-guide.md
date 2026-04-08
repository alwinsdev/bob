# BOB Reconciliation Hub
## User Operations Guide

Version: 2026-04-08
Audience: Analysts, Managers, Admins, Operations users

## 1) What This System Does
BOB Reconciliation Hub helps teams:
- import carrier and source files
- reconcile records with deterministic matching
- review pending or flagged records
- apply corrections with controlled workflows
- export commission-ready and impact-ready outputs

## 2) Roles and Access
Role access is permission-driven. Typical capabilities:
- Reconciliation Analyst:
  - view reconciliation screens
  - lock, resolve, and flag records
  - view reporting outputs
- Manager:
  - analyst capabilities plus ETL run/reanalysis, bulk operations, export
- Admin:
  - manager capabilities plus delete operations
- Super Admin:
  - full access including access-control governance

Access is configured in:
- [RolesAndPermissionsSeeder](../database/seeders/RolesAndPermissionsSeeder.php)
- [routes/web.php](../routes/web.php)

## 3) Main Screens
- Home: pipeline overview and recent activity
- Reconciliation Grid: record review and intervention workspace
- Import Feeds: upload, monitor, rerun, and download run artifacts
- Lock List: maintain final override rules by policy/contract
- Commission Dashboard: KPI and adjustment summary
- Final BOB: commission-ready resolved output
- Commission (Contract Patch Ledger): patch-level audit trail
- Locklist Impact: override effect analytics
- Audit Logs: combined analyst and patch audit history
- Access Control: roles and permission governance
- Settings: preferences (theme, grid density, export format)

## 4) Standard Workflow: Upload to Final Output

### Step 1: Upload a standard run
Go to Import Feeds and upload:
- Carrier file (required)
- IMS file (optional, but at least IMS or Health Sherpa is required)
- Health Sherpa file (optional, but at least IMS or Health Sherpa is required)
- Payee file (optional)

After submit:
- run status becomes pending, then processing
- background job handles ETL

### Step 2: Monitor progress
In Import Feeds, each run shows:
- processed count
- failed count
- matched counters
- status and error summary

Possible statuses:
- pending
- processing
- completed
- completed_with_errors
- failed

### Step 3: Review queue records
Open Reconciliation Grid to review records.
Records can appear as:
- pending
- matched
- flagged
- resolved
- skipped

### Step 4: Lock before editing
To avoid conflicts, lock the record before resolve/flag.
If record is locked by another user, action is blocked until release/expiry.

### Step 5: Resolve or flag
Resolve requires:
- aligned agent code
- compensation type (New or Renewal)

Flag sets exception marker for follow-up.
Current flag values enforced by API:
- Home Open
- Home Close

### Step 6: Optional lock list promotion
Use single or bulk promote-to-locklist when a corrected assignment should become future override policy.

### Step 7: Export and reporting
Use reporting screens for:
- Final BOB export
- locklist impact export
- contract patch ledger traceability

Export formats depend on user settings:
- xlsx
- csv
- pdf

## 5) Contract Patch Workflow (Mid-Week Adjustments)

### Step 1: Start contract patch run
From Import Feeds, upload contract patch file.
System automatically targets the latest completed standard run.

### Step 2: Processing behavior
Patch run executes asynchronously and writes:
- patch run counters (processed, patched, skipped, failed)
- output workbook
- immutable patch log rows

### Step 3: Review results
Use Commission screen (contract patch ledger) to inspect:
- old values
- new values
- operator
- patch timestamp
- patch run summary

### Step 4: Download patch output
Download is available once output exists and your role has export permission.

## 6) Reanalysis (Rerun) Workflow

### Standard run rerun
- supported for top-level standard runs that are not currently in progress
- can rerun in place with new or reused files
- run metadata tracks attempt number, retry group, and reason

### Contract patch rerun
- supported for contract patch runs
- can replace patch file or reuse existing one
- previous patch mutations are rolled back before rerun applies fresh changes

## 7) Lock List Management Workflow

### What lock list does
If a contract/policy exists in lock list, that entry can override matched values during ETL.

### Common operations
- add a single lock list rule
- import lock list file
- update existing lock list rules
- export full lock list
- delete obsolete lock list rules

## 8) Troubleshooting

### No records matched
Check:
1. source headers match supported names
2. at least one source file (IMS or Health Sherpa) is provided
3. BOB has required columns and valid identifiers
4. queue worker is running

### Run stuck in pending/processing
Check:
1. queue worker is running
2. failed_jobs table for failures
3. import batch error message and row error summaries

### Cannot edit a record
Check:
1. record lock owner
2. whether your lock expired
3. your role has reconciliation.edit permission

### Download unavailable
Check:
1. run has generated output
2. your role has reconciliation.export.download permission

### Permission denied (403)
Check:
1. assigned role and permissions
2. permission cache refresh after RBAC changes
3. route-level permission requirement

## 9) Best Practices for Analysts
- Always lock before action.
- Use precise agent codes for resolution.
- Use flagging for records requiring business escalation.
- Promote to lock list only for stable, policy-level overrides.
- Prefer rerun only when source data corrections justify recomputation.

## 10) Best Practices for Managers/Admins
- Monitor failed and skipped trends per run.
- Audit bulk operations periodically.
- Keep lock list clean and intentional.
- Validate reporting batch context before exports.
- Ensure queue workers and scheduler are healthy.

## 11) Reference Links
- [Developer Architecture Guide](./developer-architecture-guide.md)
- [Flow Diagrams](./flow-diagrams.md)
- [README](../README.md)
