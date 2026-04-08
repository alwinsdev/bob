<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reconciliation Hub Report</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #333; margin: 0; padding: 0; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #6366f1; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #1e1b4b; font-size: 20px; }
        .header p { margin: 5px 0 0; color: #64748b; font-size: 11px; }
        .filter-info { background: #f1f5f9; padding: 8px; border-radius: 4px; margin-bottom: 15px; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; }
        th { background-color: #f8fafc; color: #475569; font-weight: bold; text-align: left; padding: 8px; border-bottom: 1px solid #e2e8f0; text-transform: uppercase; font-size: 8px; }
        td { padding: 8px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        .footer { position: fixed; bottom: -30px; left: 0; right: 0; height: 30px; text-align: center; font-size: 8px; color: #94a3b8; }
        .status-pill { padding: 2px 5px; border-radius: 3px; font-weight: bold; font-size: 8px; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-flagged { background: #fee2e2; color: #991b1b; }
        .status-resolved { background: #d1fae5; color: #065f46; }
        .status-matched { background: #e0e7ff; color: #3730a3; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reconciliation Hub Dataset</h1>
        <p>Generated on {{ now()->format('F j, Y \a\t g:i A') }}</p>
    </div>

    <div class="filter-info">
        <strong>Filters Applied:</strong> 
        Status: {{ ucfirst($status) }} | 
        Search: {{ $search ?: 'None' }} |
        Total Records: {{ count($records) }}
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Status</th>
                <th>Contract ID</th>
                <th>Member Name</th>
                <th>Carrier</th>
                <th>Source/Method</th>
                <th>Score</th>
                <th>Agent</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $record)
            <tr>
                <td>{{ substr($record->id, -6) }}</td>
                <td>
                    <span class="status-pill status-{{ strtolower($record->status) }}">
                        {{ strtoupper($record->status) }}
                    </span>
                </td>
                <td><strong>{{ $record->contract_id }}</strong></td>
                <td>{{ trim($record->member_first_name . ' ' . $record->member_last_name) }}</td>
                <td>{{ $record->carrier }}</td>
                <td>{{ $record->match_method_label }}</td>
                <td>{{ $record->match_confidence ? $record->match_confidence . '%' : '—' }}</td>
                <td>{{ $record->aligned_agent_name ?: '—' }}</td>
                <td>{{ $record->created_at->format('m/d/y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        BOB System - Reconciliation Hub. Page <span class="pagenum"></span>
    </div>
</body>
</html>
