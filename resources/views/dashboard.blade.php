@extends('layouts.app')

@section('content')
    <h1>Dashboard</h1>
    <p class="muted">Ringkasan fitur utama project untuk localhost.</p>

    <div class="row">
        <div class="col-4">
            <div class="card">
                <h3>Total Item</h3>
                <p style="font-size: 28px; margin: 4px 0;">{{ $itemCount }}</p>
            </div>
        </div>
        <div class="col-4">
            <div class="card">
                <h3>Total Tracking Event</h3>
                <p style="font-size: 28px; margin: 4px 0;">{{ $eventCount }}</p>
            </div>
        </div>
        <div class="col-4">
            <div class="card">
                <h3>Total Hash Chain Log</h3>
                <p style="font-size: 28px; margin: 4px 0;">{{ $logCount }}</p>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="stack" style="justify-content: space-between; margin-bottom: 12px;">
                    <h3 style="margin: 0;">Aksi Cepat</h3>
                    <div class="stack">
                        <a class="btn secondary" href="{{ route('items.index') }}">Lihat Item</a>
                        @if(in_array($user->role, ['admin', 'supplier'], true))
                            <a class="btn" href="{{ route('items.create') }}">Input Item Baru</a>
                        @endif
                        @if($user->role === 'admin')
                            <a class="btn" href="{{ route('integrity.index') }}">Jalankan Integrity Check</a>
                            <a class="btn secondary" href="{{ route('audit.index') }}">Lihat Audit Trail</a>
                        @endif
                    </div>
                </div>

                <table>
                    <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Supplier</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($recentItems as $item)
                        <tr>
                            <td>{{ $item->item_code }}</td>
                            <td>{{ $item->item_name }}</td>
                            <td>{{ $item->supplier->name }}</td>
                            <td><span class="badge">{{ $item->current_status }}</span></td>
                            <td><a href="{{ route('items.show', $item) }}">Detail</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="muted">Belum ada item.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
