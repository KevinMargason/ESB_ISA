@extends('layouts.app')

@section('content')
    <h1>Input Data Barang</h1>
    <p class="muted">Role yang bisa input: admin dan supplier.</p>

    <div class="row">
        <div class="col-8">
            <div class="card">
                <form action="{{ route('items.store') }}" method="POST">
                    @csrf

                    <div class="form-group">
                        <label for="item_code">Kode Item</label>
                        <input id="item_code" name="item_code" value="{{ old('item_code') }}" required>
                    </div>

                    <div class="form-group">
                        <label for="item_name">Nama Item</label>
                        <input id="item_name" name="item_name" value="{{ old('item_name') }}" required>
                    </div>

                    <div class="form-group">
                        <label for="category">Kategori</label>
                        <input id="category" name="category" value="{{ old('category') }}" required>
                    </div>

                    <div class="form-group">
                        <label for="quantity">Jumlah</label>
                        <input id="quantity" name="quantity" type="number" min="1" value="{{ old('quantity', 1) }}" required>
                    </div>

                    @if($user->role === 'admin')
                        <div class="form-group">
                            <label for="supplier_id">Supplier</label>
                            <select id="supplier_id" name="supplier_id" required>
                                <option value="">Pilih supplier</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>
                                        {{ $supplier->name }} ({{ $supplier->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="form-group">
                        <label for="sensitive_notes">Catatan Sensitif (opsional, akan dienkripsi)</label>
                        <textarea id="sensitive_notes" name="sensitive_notes" rows="4">{{ old('sensitive_notes') }}</textarea>
                    </div>

                    <button class="btn" type="submit">Simpan Item</button>
                    <a class="btn secondary" href="{{ route('items.index') }}">Kembali</a>
                </form>
            </div>
        </div>
    </div>
@endsection
