@extends('layouts.app')

@section('content')
    <div class="section-head">
        <div class="section-copy">
            <h1>Integrity Verification</h1>
            <p class="muted">Halaman ini untuk admin mengecek apakah hash chain transaction log masih valid.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-6">
            <div class="card hero-card">
                <h3>Hasil Verifikasi</h3>
                <p>Status:
                    @if($result['valid'])
                        <span class="badge" style="background:#e8f8ed; color:#156b2f;">VALID</span>
                    @else
                        <span class="badge" style="background:#ffefef; color:#a62521;">TAMPERED</span>
                    @endif
                </p>
                <p>Total log dicek: {{ $result['total_logs'] }}</p>
                <p>Waktu cek: {{ $result['checked_at']->format('Y-m-d H:i:s') }}</p>

                @if(! $result['valid'])
                    <div class="alert error">
                        Mismatch di chain_index {{ $result['mismatch']['chain_index'] }}.
                    </div>
                @endif
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <h3>20 Log Terbaru</h3>
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Event</th>
                            <th>Ref</th>
                            <th>Aktor</th>
                            <th>Current Hash</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($latestLogs as $log)
                            <tr>
                                <td>{{ $log->chain_index }}</td>
                                <td>{{ $log->event_name }}</td>
                                <td>{{ $log->ref_type }}:{{ $log->ref_id }}</td>
                                <td>{{ $log->actor?->name ?? '-' }}</td>
                                <td class="mono">{{ $log->current_hash }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="muted">Belum ada transaction log.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
