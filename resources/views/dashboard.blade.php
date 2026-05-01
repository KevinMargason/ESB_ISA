@extends('layouts.app')

@section('content')
    <div class="section-head">
        <div class="section-copy">
            <h1>Dashboard Operasional</h1>
            <p class="muted">Ringkasan aktivitas utama Secure Supply Chain Tracking System di lingkungan localhost.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-4">
            <div class="card hero-card subtle-card">
                <h3>Total Item</h3>
                <p class="stat-value">{{ $itemCount }}</p>
            </div>
        </div>
        <div class="col-4">
            <div class="card hero-card subtle-card">
                <h3>Total Tracking Event</h3>
                <p class="stat-value">{{ $eventCount }}</p>
            </div>
        </div>
        <div class="col-4">
            <div class="card hero-card subtle-card">
                <h3>Total Hash Chain Log</h3>
                <p class="stat-value">{{ $logCount }}</p>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="section-head">
                    <h3>Aksi Cepat</h3>
                    <div class="stack" style="gap: 8px;">
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

                <div class="table-wrap">
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
    </div>
@endsection

