@extends('layouts.app')

@section('content')
    <div class="row" style="justify-content: center;">
        <div class="col-6">
            <div class="card">
                <h1>Register</h1>
                <p class="muted">Untuk mode localhost pembagian role bisa langsung dipilih saat register.</p>

                <form action="{{ route('register.store') }}" method="POST">
                    @csrf

                    <div class="form-group">
                        <label for="name">Nama</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required>
                    </div>

                    <div class="form-group">
                        <label for="role">Role</label>
                        <select id="role" name="role" required>
                            <option value="">Pilih role</option>
                            <option value="supplier" @selected(old('role') === 'supplier')>supplier</option>
                            <option value="kurir" @selected(old('role') === 'kurir')>kurir</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" required>
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation">Konfirmasi Password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required>
                    </div>

                    <button type="submit" class="btn">Buat Akun</button>
                    <a href="{{ route('login') }}" class="btn secondary">Kembali ke Login</a>
                </form>
            </div>
        </div>
    </div>
@endsection
