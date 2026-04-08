<?php

namespace App\Http\Controllers\Reconciliation;

use App\Http\Controllers\Controller;
use App\Models\LockList;
use App\Models\ReconciliationAuditLog;
use App\Exports\LockListExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Spatie\SimpleExcel\SimpleExcelReader;


/**
 * LockListController
 *
 * Manages the Lock List — the "Final Authority" table that forces
 * agent/department/payee values onto BOB records regardless of IMS or HS matches.
 *
 * Authorization is handled exclusively at the route layer via Spatie permission middleware.
 * Do NOT add inline permission checks to this controller — that would violate the
 * single-authorization-source-of-truth principle.
 *
 * Route Permission Map:
 *  - index / data                         → reconciliation.view
 *  - export                               → reconciliation.export.download
 *  - store / update / import              → reconciliation.bulk_approve
 *  - destroy                              → reconciliation.delete
 */
class LockListController extends Controller
{
    // ── View ────────────────────────────────────────────────────────────────

    public function index()
    {
        $totalEntries = LockList::count();

        return view('reconciliation.locklist', compact('totalEntries'));
    }

    // ── AG Grid JSON Data ────────────────────────────────────────────────────

    public function data(Request $request)
    {
        $query = LockList::query()
            ->select(['id', 'policy_id', 'agent_name', 'department', 'payee_name', 'created_at', 'updated_at',
                       'promoted_from_batch_id', 'promoted_by']);

        // Quick search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('policy_id', 'like', "%{$search}%")
                  ->orWhere('agent_name', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%")
                  ->orWhere('payee_name', 'like', "%{$search}%");
            });
        }

        $total = $query->count();

        // AG Grid server-side pagination
        $start = (int) $request->input('startRow', 0);
        $end   = (int) $request->input('endRow', 100);
        $limit = max(1, $end - $start);

        $rows = $query
            ->orderBy('updated_at', 'desc')
            ->skip($start)
            ->take($limit)
            ->get()
            ->map(fn (LockList $ll) => [
                'id'          => $ll->id,
                'policy_id'   => $ll->policy_id,
                'agent_name'  => $ll->agent_name,
                'department'  => $ll->department,
                'payee_name'  => $ll->payee_name,
                'created_at'  => $ll->created_at?->format('M d, Y'),
                'updated_at'  => $ll->updated_at?->format('M d, Y H:i'),
                'is_promoted' => (bool) $ll->promoted_from_batch_id,
            ]);

        return response()->json([
            'rows'       => $rows,
            'totalCount' => $total,
        ]);
    }

    // ── Create ───────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $data = $request->validate([
            'policy_id'  => ['required', 'string', 'max:255', Rule::unique('lock_lists', 'policy_id')],
            'agent_name' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'payee_name' => ['nullable', 'string', 'max:255'],
        ]);

        $lockList = LockList::create($data);

        $this->auditLockListChange('locklist_created', $lockList->policy_id);

        return response()->json([
            'success' => true,
            'message' => "Policy ID {$lockList->policy_id} added to Lock List.",
            'entry'   => $lockList,
        ]);
    }

    // ── Update ───────────────────────────────────────────────────────────────

    public function update(Request $request, LockList $lockList)
    {
        $data = $request->validate([
            'agent_name' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'payee_name' => ['nullable', 'string', 'max:255'],
        ]);

        $lockList->update($data);

        $this->auditLockListChange('locklist_updated', $lockList->policy_id);

        return response()->json([
            'success' => true,
            'message' => "Lock List entry for {$lockList->policy_id} updated.",
        ]);
    }

    // ── Delete ───────────────────────────────────────────────────────────────

    public function destroy(LockList $lockList)
    {
        $policyId = $lockList->policy_id;
        $lockList->delete();

        $this->auditLockListChange('locklist_deleted', $policyId);

        return response()->json([
            'success' => true,
            'message' => "Policy ID {$policyId} removed from Lock List.",
        ]);
    }

    // ── Bulk Import (Excel/CSV) ───────────────────────────────────────────────

    /**
     * Expected headers: CONTRACT_ID | AGENT_NAME | DEPARTMENT_NAME | PAYEE_NAME
     * These must be UPPERCASE and CONTRACT_ID must match BOB's policy ID format.
     */
    public function import(Request $request)
    {
        $request->validate([
            'import_file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $file = $request->file('import_file');
        $path = $file->storeAs('tmp_imports', 'locklist_import_' . now()->timestamp . '.' . $file->getClientOriginalExtension(), 'local');
        $fullPath = Storage::disk('local')->path($path);

        $inserted = 0;
        $updated  = 0;
        $errors   = [];

        try {
            SimpleExcelReader::create($fullPath)->headerOnRow(0)->getRows()->each(function (array $row) use (&$inserted, &$updated, &$errors) {
                $policyId   = trim((string) ($row['CONTRACT_ID'] ?? ''));
                $agentName  = trim((string) ($row['AGENT_NAME'] ?? ''));
                $department = trim((string) ($row['DEPARTMENT_NAME'] ?? $row['DEPARTMENT'] ?? ''));
                $payeeName  = trim((string) ($row['PAYEE_NAME'] ?? ''));

                if (empty($policyId)) {
                    $errors[] = 'Skipped row: CONTRACT_ID is empty.';
                    return;
                }

                $existing = LockList::where('policy_id', $policyId)->first();

                if ($existing) {
                    $existing->update([
                        'agent_name' => $agentName ?: $existing->agent_name,
                        'department' => $department ?: $existing->department,
                        'payee_name' => $payeeName ?: $existing->payee_name,
                    ]);
                    $updated++;
                } else {
                    LockList::create([
                        'policy_id'  => $policyId,
                        'agent_name' => $agentName ?: null,
                        'department' => $department ?: null,
                        'payee_name' => $payeeName ?: null,
                    ]);
                    $inserted++;
                }
            });
        } catch (\Exception $e) {
            Log::error('[LockList Import] Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Import failed: ' . $e->getMessage()], 422);
        } finally {
            Storage::disk('local')->delete($path);
        }

        $this->auditLockListChange('locklist_imported', "Inserted:{$inserted} Updated:{$updated}");

        return response()->json([
            'success'  => true,
            'message'  => "Import complete. {$inserted} added, {$updated} updated." . (count($errors) ? ' ' . count($errors) . ' rows skipped.' : ''),
            'inserted' => $inserted,
            'updated'  => $updated,
            'errors'   => $errors,
        ]);
    }

    // ── Export (Excel) ────────────────────────────────────────────────────────

    /**
     * Export the Lock List based on user preference (XLSX, CSV, PDF).
     */
    public function export(Request $request)
    {
        $user = $request->user();
        $prefs = $user->preferences ?? [];
        $format = $prefs['export_format'] ?? 'xlsx';
        
        $search = $request->input('search');
        $fileName = 'Master_LockList_' . now()->format('Ymd_His');

        $query = LockList::query()->orderBy('policy_id');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('policy_id', 'like', "%{$search}%")
                  ->orWhere('agent_name', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%")
                  ->orWhere('payee_name', 'like', "%{$search}%");
            });
        }

        if ($format === 'pdf') {
            $records = $query->get();
            $pdf = Pdf::loadView('reports.locklist-pdf', ['records' => $records])
                ->setPaper('a4', 'landscape');
            return $pdf->download($fileName . '.pdf');
        }

        $export = new LockListExport($search);
        $ext = in_array($format, ['xlsx', 'csv']) ? $format : 'xlsx';
        
        return $export->download($fileName . '.' . $ext);
    }



    // ── Private Helpers ───────────────────────────────────────────────────────

    private function auditLockListChange(string $action, string $context): void
    {
        try {
            ReconciliationAuditLog::create([
                'transaction_id'      => 'LOCKLIST',
                'action'              => $action,
                'modified_by_user_id' => auth()->id(),
                'ip_address'          => request()->ip(),
                'user_agent'          => request()->userAgent(),
                'notes'               => $context,
            ]);
        } catch (\Exception $e) {
            Log::warning('[LockList Audit] Failed to write audit: ' . $e->getMessage());
        }
    }

}
