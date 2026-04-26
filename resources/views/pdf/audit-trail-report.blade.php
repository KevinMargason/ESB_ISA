<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Audit Trail Report</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 12px; }
        h1 { margin: 0; font-size: 20px; }
        p { margin: 4px 0; }
        .header { margin-bottom: 16px; padding-bottom: 10px; border-bottom: 2px solid #111827; }
        .meta { margin-top: 8px; color: #374151; }
        .filters { margin: 14px 0; padding: 10px; background: #f3f4f6; border: 1px solid #d1d5db; }
        .filters strong { display: inline-block; min-width: 95px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #d1d5db; padding: 6px; vertical-align: top; }
        th { background: #e5e7eb; text-align: left; }
        .muted { color: #6b7280; }
        .details { font-size: 10px; white-space: pre-wrap; word-break: break-word; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Audit Trail</h1>
        <p class="meta">Generated at: {{ $generatedAt }}</p>
        <p class="meta">Total data: {{ $audits->count() }}</p>
    </div>

    <div class="filters">
        <p><strong>Action:</strong> {{ $filters['action'] !== '' ? $filters['action'] : 'Semua' }}</p>
        <p><strong>Target Type:</strong> {{ $filters['target_type'] !== '' ? $filters['target_type'] : 'Semua' }}</p>
        <p><strong>User ID:</strong> {{ $filters['user_id'] > 0 ? $filters['user_id'] : 'Semua' }}</p>
        <p><strong>Date From:</strong> {{ $filters['date_from'] !== '' ? $filters['date_from'] : '-' }}</p>
        <p><strong>Date To:</strong> {{ $filters['date_to'] !== '' ? $filters['date_to'] : '-' }}</p>
    </div>

    <table>
        <thead>
        <tr>
            <th style="width: 14%;">Waktu</th>
            <th style="width: 15%;">User</th>
            <th style="width: 14%;">Action</th>
            <th style="width: 12%;">Target</th>
            <th style="width: 12%;">IP</th>
            <th style="width: 33%;">Details</th>
        </tr>
        </thead>
        <tbody>
        @forelse($audits as $audit)
            <tr>
                <td>{{ $audit->created_at?->format('Y-m-d H:i:s') ?? '-' }}</td>
                <td>{{ $audit->user?->name ?? '-' }}@if($audit->user) ({{ $audit->user->role }}) @endif</td>
                <td>{{ $audit->action }}</td>
                <td>{{ $audit->target_type }}:{{ $audit->target_id ?? '-' }}</td>
                <td>{{ $audit->ip_address ?? '-' }}</td>
                <td class="details">
                    @if(is_array($audit->details) && count($audit->details) > 0)
                        {{ json_encode($audit->details, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}
                    @else
                        -
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="muted">Tidak ada data audit untuk filter ini.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
