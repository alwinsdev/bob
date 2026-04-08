<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Lock List Impact Report</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #333; margin: 0; padding: 0; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #a855f7; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #1e1b4b; font-size: 22px; }
        .header p { margin: 5px 0 0; color: #64748b; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background-color: #f8fafc; color: #475569; font-weight: bold; text-align: left; padding: 10px; border-bottom: 1px solid #e2e8f0; text-transform: uppercase; font-size: 9px; letter-spacing: 0.05em; }
        td { padding: 10px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        .footer { position: fixed; bottom: -30px; left: 0; right: 0; height: 30px; text-align: center; font-size: 9px; color: #94a3b8; }
        .agent-change { display: inline-flex; align-items: center; gap: 5px; }
        .agent-old { color: #94a3b8; text-decoration: line-through; margin-right: 5px; }
        .agent-new { color: #7c3aed; font-weight: bold; }
        .impact-pill { background: #f3e8ff; color: #7e22ce; padding: 2px 8px; border-radius: 9999px; font-weight: bold; font-size: 9px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Lock List Impact Assessment</h1>
        <p>Showing records overridden by the Master Lock List</p>
        <p>Published: {{ now()->format('F j, Y \a\t g:i A') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Contract ID</th>
                <th>Affected Member</th>
                <th>Original Logic</th>
                <th>Agent Change (Before → Final)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $record)
            @php
                $sourceBefore = $record->original_match_method ? 
                    match (true) {
                        str_starts_with($record->original_match_method, 'ims:') => 'IMS',
                        str_starts_with($record->original_match_method, 'hs:') => 'Health Sherpa',
                        default => 'Other'
                    } : 'Unmatched';
                $memberName = trim($record->member_first_name . ' ' . $record->member_last_name);
            @endphp
            <tr>
                <td><strong>{{ $record->contract_id }}</strong></td>
                <td>{{ $memberName ?: '—' }}</td>
                <td>{{ $sourceBefore }}</td>
                <td>
                    <span class="agent-old">{{ $record->original_agent_name ?: 'None' }}</span>
                    <span style="color: #6366f1;">→</span>
                    <span class="agent-new">{{ $record->aligned_agent_name }}</span>
                </td>
                <td><span class="impact-pill">LOCKED</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        © {{ date('Y') }} ENS Environmental Consultancy - Internal Reconciliation System. Page <span class="pagenum"></span>
    </div>
</body>
</html>
