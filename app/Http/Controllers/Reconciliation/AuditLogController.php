<?php

namespace App\Http\Controllers\Reconciliation;

use App\Http\Controllers\Controller;
use App\Models\ReconciliationAuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Display the audit logs page.
     */
    public function index()
    {
        return view('reconciliation.audit-logs', [
            'pageTitle' => 'System Audit Logs',
            'pageSubtitle' => 'Monitor the trail of interventions, resolutions, and system changes'
        ]);
    }

    /**
     * Fetch paginated audit log data for AG Grid.
     */
    public function data(Request $request)
    {
        $auditLogs = \Illuminate\Support\Facades\DB::table('reconciliation_audit_logs')
            ->leftJoin('users', 'reconciliation_audit_logs.modified_by_user_id', '=', 'users.id')
            ->select([
                'reconciliation_audit_logs.id',
                'reconciliation_audit_logs.transaction_id',
                'reconciliation_audit_logs.action',
                'reconciliation_audit_logs.previous_agent_code',
                'reconciliation_audit_logs.new_agent_code',
                'reconciliation_audit_logs.modified_by_user_id',
                'users.name as user_name',
                'users.email as user_email',
                'reconciliation_audit_logs.created_at',
                'reconciliation_audit_logs.notes'
            ]);

        $patchLogs = \Illuminate\Support\Facades\DB::table('contract_patch_logs')
            ->leftJoin('users', 'contract_patch_logs.updated_by', '=', 'users.id')
            ->select([
                'contract_patch_logs.id',
                'contract_patch_logs.contract_id as transaction_id',
                'contract_patch_logs.change_type as action',
                'contract_patch_logs.old_agent_code as previous_agent_code',
                'contract_patch_logs.new_agent_code',
                'contract_patch_logs.updated_by as modified_by_user_id',
                'users.name as user_name',
                'users.email as user_email',
                'contract_patch_logs.created_at',
                \Illuminate\Support\Facades\DB::raw("'Contract patched via automated engine' as notes")
            ]);

        $query = \Illuminate\Support\Facades\DB::table(\Illuminate\Support\Facades\DB::raw("({$auditLogs->toSql()} UNION {$patchLogs->toSql()}) as combined_logs"))
            ->mergeBindings($auditLogs)
            ->mergeBindings($patchLogs);

        $allowedSortFields = [
            'created_at' => 'created_at',
            'action' => 'action',
            'transaction_id' => 'transaction_id',
            'user' => 'user_name',
        ];

        if ($request->has('actionType') && $request->actionType !== 'all') {
            $query->where('action', $request->actionType);
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%");
            });
        }

        $sortModel = [];
        if ($request->filled('sortModel')) {
            $decoded = json_decode((string) $request->sortModel, true);
            if (is_array($decoded)) {
                $sortModel = $decoded;
            }
        }

        if (!empty($sortModel)) {
            foreach ($sortModel as $sort) {
                $colId = $sort['colId'] ?? null;
                $field = $allowedSortFields[$colId] ?? null;
                if ($field) {
                    $order = strtolower((string) ($sort['sort'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
                    $query->orderBy($field, $order);
                }
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $perPage = $request->input('limit', 50);
        $page = $request->input('page', 1);

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        // Map items back to expected format for the frontend
        $mappedItems = collect($paginator->items())->map(function ($item) {
            return [
                'id' => $item->id,
                'transaction_id' => $item->transaction_id,
                'action' => $item->action,
                'previous_agent_code' => $item->previous_agent_code,
                'new_agent_code' => $item->new_agent_code,
                'modified_by_user_id' => $item->modified_by_user_id,
                'created_at' => $item->created_at,
                'notes' => $item->notes,
                'modified_by' => [
                    'id' => $item->modified_by_user_id,
                    'name' => $item->user_name,
                    'email' => $item->user_email,
                ]
            ];
        });

        return response()->json([
            'data' => $mappedItems,
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ]);
    }
}
