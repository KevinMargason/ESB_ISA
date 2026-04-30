@php
    $expiresText = $expiresInMinutes.' menit';
@endphp

<p>Halo {{ $name }},</p>
<p>Kode OTP registrasi kamu:</p>
<p style="font-size: 22px; font-weight: bold; letter-spacing: 2px;">{{ $otpCode }}</p>
<p>Kode ini berlaku selama {{ $expiresText }}.</p>
<p>Jika kamu tidak merasa mendaftar, abaikan email ini.</p>
