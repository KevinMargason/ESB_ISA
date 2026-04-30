@extends('layouts.app')

@section('content')
    <div class="row" style="justify-content: center;">
        <div class="col-6">
            <div class="card">
                <h1>Verifikasi OTP</h1>
                <p class="muted">Masukkan kode OTP yang dikirim ke email.</p>

                <form action="{{ route('register.verify.store') }}" method="POST">
                    @csrf

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required>
                    </div>

                    <div class="form-group">
                        <label for="otp">Kode OTP</label>
                        <input id="otp" name="otp" type="text" inputmode="numeric" maxlength="6" required>
                    </div>

                    <button type="submit" class="btn">Verifikasi</button>
                    <a href="{{ route('login') }}" class="btn secondary">Kembali ke Login</a>
                </form>
            </div>
        </div>
    </div>
@endsection
