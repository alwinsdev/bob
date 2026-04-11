<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Final BOB Output Report</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #333; margin: 0; padding: 0; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #6366f1; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #1e1b4b; font-size: 22px; }
        .header p { margin: 5px 0 0; color: #64748b; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background-color: #f8fafc; color: #475569; font-weight: bold; text-align: left; padding: 10px; border-bottom: 1px solid #e2e8f0; text-transform: uppercase; font-size: 9px; letter-spacing: 0.05em; }
        td { padding: 10px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        .footer { position: fixed; bottom: -30px; left: 0; right: 0; height: 30px; text-align: center; font-size: 9px; color: #94a3b8; }
        .status-badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: bold; }
        .status-resolved { background-color: #d1fae5; color: #065f46; }
        .override-yes { color: #b91c1c; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Final BOB Reconciliation Report</h1>
        <p>Generated on {{ now()->format('F j, Y \a\t g:i A') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Contract ID</th>
                <th>Agent Name</th>
                <th>Department</th>
                <th>Payee Name</th>
                <th>Source</th>
                <th>Override</th>
                <th>Resolved At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $record)
            <tr>
                <td><strong>{{ $record->contract_id }}</strong></td>
                <td>{{ $record->aligned_agent_name }}</td>
                <td>{{ $record->group_team_sales }}</td>
                <td>{{ $record->payee_name }}</td>
                <td>{{ $record->match_method_label }}</td>
                <td>
                    <span class="{{ $record->override_flag ? 'override-yes' : '' }}">
                        {{ $record->override_flag ? 'YES' : 'No' }}
                    </span>
                </td>
                <td>{{ $record->resolved_at ? $record->resolved_at->format('m/d/Y H:i') : '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        © {{ date('Y') }} BOB Reconciliation System. Page <span class="pagenum"></span>
    </div>
</body>
</html>
