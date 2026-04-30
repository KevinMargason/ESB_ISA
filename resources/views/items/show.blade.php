@extends('layouts.app')

@section('content')
    <div class="section-head">
        <div class="section-copy">
            <h1>Detail Item {{ $item->item_code }}</h1>
            <p class="muted" style="margin: 0;">Tracking lengkap dan update status.</p>
        </div>
        <a class="btn secondary" href="{{ route('items.index') }}">Kembali ke daftar</a>
    </div>

    <div class="row">
        <div class="col-6">
            <div class="card hero-card">
                <h3>Informasi Barang</h3>
                <div class="table-wrap">
                    <table>
                        <tr><th>Kode</th><td>{{ $item->item_code }}</td></tr>
                        <tr><th>Nama</th><td>{{ $item->item_name }}</td></tr>
                        <tr><th>Kategori</th><td>{{ $item->category }}</td></tr>
                        <tr><th>Jumlah</th><td>{{ $item->quantity }}</td></tr>
                        <tr><th>Supplier</th><td>{{ $item->supplier->name }}</td></tr>
                        <tr><th>Status Saat Ini</th><td><span class="badge">{{ $item->current_status }}</span></td></tr>
                        <tr><th>Catatan Sensitif (decrypted)</th><td>{{ $decryptedNotes ?? '-' }}</td></tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-6">
            <div class="card hero-card">
                <h3>Update Status</h3>

                @if($canUpdateStatus)
                    <form action="{{ route('tracking.store', $item) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="status">Status Baru</label>
                            <select id="status" name="status" required>
                                <option value="">Pilih status</option>
                                @foreach($statuses as $status)
                                    @if($status !== $item->current_status)
                                        <option value="{{ $status }}">{{ $status }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="notes">Catatan (opsional)</label>
                            <textarea id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                        </div>

                        <button class="btn" type="submit">Update Status</button>
                    </form>
                @else
                    <p class="muted">Role anda tidak memiliki izin update status.</p>
                @endif
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <h3>Riwayat Tracking</h3>
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Status</th>
                            <th>Aktor</th>
                            <th>Catatan</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($item->trackingEvents as $event)
                            <tr>
                                <td>{{ $event->event_time->format('Y-m-d H:i:s') }}</td>
                                <td><span class="badge">{{ $event->status }}</span></td>
                                <td>{{ $event->actor->name }} ({{ $event->actor->role }})</td>
                                <td>{{ $event->notes ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="muted">Belum ada riwayat tracking.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
