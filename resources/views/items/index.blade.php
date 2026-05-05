@extends('layouts.app')

@section('content')
    <div class="section-head">
        <div class="section-copy">
            <h1>Data Item</h1>
            <p class="muted" style="margin: 0;">Tracking status barang Anda</p>
        </div>
        @if(in_array($user->role, ['admin', 'supplier'], true))
            <a class="btn" href="{{ route('items.create') }}">Input Item</a>
        @endif
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama</th>
                    <th>Kategori</th>
                    <th>Quantity</th>
                    <th>Supplier</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
                </thead>
                <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>{{ $item->item_code }}</td>
                        <td>{{ $item->item_name }}</td>
                        <td>{{ $item->category }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ $item->supplier->name }}</td>
                        <td><span class="badge">{{ $item->current_status }}</span></td>
                        <td><a href="{{ route('items.show', $item) }}">Detail</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="muted">Belum ada data item.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:12px;">
            {{ $items->links() }}
        </div>
    </div>
@endsection
