@extends('layouts.app')

@section('content')
    <div class="auth-shell">
        <div class="auth-panel">
            <div class="card hero-card subtle-card">
                <span class="eyebrow">Secure Access</span>
                <h1>Login</h1>
                <p class="muted">Masuk untuk menggunakan Secure Supply Chain Tracking System.</p>

                <form action="{{ route('login.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" required>
                    </div>

                    <button type="submit" class="btn">Login</button>
                    <a href="{{ route('register') }}" class="btn secondary">Register</a>
                </form>

                <hr>
                <p class="muted" style="margin:0;">Demo seed account:</p>
                <ul class="muted">
                    <li>admin@local.test / password123</li>
                    <li>supplier@local.test / password123</li>
                    <li>kurir@local.test / password123</li>
                </ul>
            </div>
        </div>
    </div>
@endsection