@extends('layouts.app')

@section('content')
    <div class="section-head">
        <div class="section-copy">
            <h1>Audit Trail</h1>
            <p class="muted">Riwayat aktivitas keamanan untuk admin, termasuk login, akses, tracking, dan integrity check.</p>
        </div>
    </div>

    <div class="card hero-card" style="margin-bottom: 14px;">
        <h3>Filter</h3>
        <form method="GET" action="{{ route('audit.index') }}">
            <div class="row">
                <div class="col-4">
                    <div class="form-group">
                        <label for="action">Action</label>
                        <input id="action" name="action" value="{{ $filters['action'] }}" placeholder="contoh: LOGIN or SENSITIVE">
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label for="target_type">Target Type</label>
                        <select id="target_type" name="target_type">
                            <option value="">Semua</option>
                            @foreach($targetTypes as $targetType)
                                <option value="{{ $targetType }}" @selected($filters['target_type'] === $targetType)>{{ $targetType }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label for="user_id">User</label>
                        <select id="user_id" name="user_id">
                            <option value="0">Semua</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected((int) $filters['user_id'] === $user->id)>
                                    {{ $user->name }} ({{ $user->role }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-4">
                    <div class="form-group">
                        <label for="date_from">Date From</label>
                        <input id="date_from" type="date" name="date_from" value="{{ $filters['date_from'] }}">
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label for="date_to">Date To</label>
                        <input id="date_to" type="date" name="date_to" value="{{ $filters['date_to'] }}">
                    </div>
                </div>
                <div class="col-4" style="display:flex; align-items:center; gap:8px; height:100%; margin-top:6.5px;">
                    <button class="btn" style="height:40px; padding:0 18px; display:flex; align-items:center;">Terapkan Filter</button>
                    <a class="btn secondary" style="height:40px; padding:0 18px; display:flex; align-items:center;" href="{{ route('audit.index') }}">Reset</a>
                    <a class="btn secondary" style="height:40px; padding:0 18px; display:flex; align-items:center;" href="{{ route('audit.export-pdf', request()->query()) }}">Export PDF</a>
                </div>
            </div>
        </form>

        @if($actions->isNotEmpty())
            <p class="muted" style="margin-top: 10px; margin-bottom: 0;">
                Action terdeteksi: {{ $actions->implode(', ') }}
            </p>
        @endif
    </div>

    <div class="card">
        <h3>Data Audit</h3>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Waktu</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Target</th>
                    <th>IP</th>
                    <th>Details</th>
                </tr>
                </thead>
                <tbody>
                @forelse($audits as $audit)
                    <tr>
                        <td>{{ $audit->created_at->format('Y-m-d H:i:s') }}</td>
                        <td>{{ $audit->user?->name ?? '-' }} @if($audit->user) ({{ $audit->user->role }}) @endif</td>
                        <td><span class="badge">{{ $audit->action }}</span></td>
                        <td>{{ $audit->target_type }}:{{ $audit->target_id ?? '-' }}</td>
                        <td>{{ $audit->ip_address ?? '-' }}</td>
                        <td>
                            @if(is_array($audit->details) && count($audit->details) > 0)
                                <pre class="mono" style="margin:0; white-space:pre-wrap;">{{ json_encode($audit->details, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="muted">Belum ada data audit.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($audits->hasPages())
            <div class="stack" style="margin-top: 12px; justify-content: space-between;">
                <span class="muted">
                    Menampilkan {{ $audits->firstItem() ?? 0 }} - {{ $audits->lastItem() ?? 0 }} dari {{ $audits->total() }} data
                </span>
                <div class="stack">
                    @if($audits->onFirstPage())
                        <span class="btn secondary" style="opacity:0.5; pointer-events:none;">Sebelumnya</span>
                    @else
                        <a class="btn secondary" href="{{ $audits->previousPageUrl() }}">Sebelumnya</a>
                    @endif

                    @if($audits->hasMorePages())
                        <a class="btn secondary" href="{{ $audits->nextPageUrl() }}">Berikutnya</a>
                    @else
                        <span class="btn secondary" style="opacity:0.5; pointer-events:none;">Berikutnya</span>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection
