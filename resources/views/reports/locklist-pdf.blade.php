<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Master Lock List Report</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #333; margin: 0; padding: 0; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #a855f7; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #1e1b4b; font-size: 20px; }
        .header p { margin: 5px 0 0; color: #64748b; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; }
        th { background-color: #f3e8ff; color: #6b21a8; font-weight: bold; text-align: left; padding: 8px; border-bottom: 1px solid #e9d5ff; text-transform: uppercase; font-size: 8px; }
        td { padding: 8px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        .footer { position: fixed; bottom: -30px; left: 0; right: 0; height: 30px; text-align: center; font-size: 8px; color: #94a3b8; }
        .contract-id { font-weight: bold; color: #1e1b4b; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Master Lock List Authority</h1>
        <p>Current active overrides in the Reconciliation System</p>
        <p>Published: {{ now()->format('F j, Y \a\t g:i A') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Contract ID (Policy)</th>
                <th>Agent Name</th>
                <th>Department</th>
                <th>Payee Name</th>
                <th>Created At</th>
                <th>Last Updated</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $row)
            <tr>
                <td class="contract-id">{{ $row->policy_id }}</td>
                <td>{{ $row->agent_name ?: '—' }}</td>
                <td>{{ $row->department ?: '—' }}</td>
                <td>{{ $row->payee_name ?: '—' }}</td>
                <td>{{ $row->created_at?->format('m/d/Y') }}</td>
                <td>{{ $row->updated_at?->format('m/d/Y H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        ENS Environmental Consultancy - Lock List Authority. Page <span class="pagenum"></span>
    </div>
</body>
</html>
