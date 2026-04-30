<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Mail\RegisterOtpMail;
use App\Services\AuditTrailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request, AuditTrailService $auditTrailService): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::lower($credentials['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $retryAfterSeconds = RateLimiter::availableIn($throttleKey);

            $auditTrailService->record(
                $request,
                'LOGIN_RATE_LIMITED',
                'auth',
                null,
                [
                    'email' => $credentials['email'],
                    'reason' => 'too_many_failed_attempts',
                    'retry_after_seconds' => $retryAfterSeconds,
                ]
            );

            return back()->withErrors([
                'email' => 'Terlalu banyak percobaan login. Silakan coba lagi nanti.',
            ])->onlyInput('email');
        }

        if (! Auth::attempt($credentials)) {
            RateLimiter::hit($throttleKey, 60);
            $auditTrailService->record(
                $request,
                'LOGIN_FAILED',
                'auth',
                null,
                [
                    'email' => $credentials['email'],
                    'reason' => 'invalid_credentials',
                ]
            );

            return back()->withErrors([
                'email' => 'Email atau password tidak valid.',
            ])->onlyInput('email');
        }
        $user = Auth::user();
        if ($user && $user->otp_verified_at === null) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $auditTrailService->record(
                $request,
                'LOGIN_BLOCKED_OTP',
                'auth',
                $user->id,
                [
                    'email' => $user->email,
                ]
            );

            return redirect()
                ->route('register.verify', ['email' => $user->email])
                ->withErrors(['email' => 'Akun belum diverifikasi OTP. Silakan cek email.']);
        }

        RateLimiter::clear($throttleKey);
        $auditTrailService->record(
            $request,
            'LOGIN_SUCCESS',
            'auth',
            (int) Auth::id(),
            [
                'email' => $credentials['email'],
            ]
        );

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request, AuditTrailService $auditTrailService): RedirectResponse
    {
        $allowedRoles = ['supplier', 'kurir'];
        $requestedRole = (string) $request->input('role', '');
        if ($requestedRole !== '' && ! in_array($requestedRole, $allowedRoles, true)) {
            $auditTrailService->record(
                $request,
                'REGISTER_ROLE_TAMPER_ATTEMPT',
                'auth',
                null,
                [
                    'email' => (string) $request->input('email', ''),
                    'requested_role' => $requestedRole,
                ]
            );
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in($allowedRoles)],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => $validated['password'],
        ]);

        $otpCode = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(5);
        $user->forceFill([
            'otp_code' => Hash::make($otpCode),
            'otp_expires_at' => $expiresAt,
            'otp_verified_at' => null,
        ])->save();

        Mail::to($user->email)->send(new RegisterOtpMail($user->name, $otpCode, 5));

        $auditTrailService->record(
            $request,
            'REGISTER_SUCCESS',
            'auth',
            $user->id,
            [
                'email' => $user->email,
                'role' => $user->role,
            ]
        );

        return redirect()->route('register.verify', ['email' => $user->email]);
    }

    public function showVerifyOtp(Request $request): View
    {
        return view('auth.verify-otp', [
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function verifyOtp(Request $request, AuditTrailService $auditTrailService): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();
        if (! $user) {
            return back()->withErrors(['email' => 'Email tidak ditemukan.']);
        }

        if ($user->otp_verified_at !== null) {
            return redirect()->route('login')->withErrors(['email' => 'Akun sudah terverifikasi. Silakan login.']);
        }

        if ($user->otp_expires_at === null || $user->otp_expires_at->isPast()) {
            $auditTrailService->record(
                $request,
                'OTP_EXPIRED',
                'auth',
                $user->id,
                [
                    'email' => $user->email,
                ]
            );

            return back()->withErrors(['otp' => 'OTP sudah kedaluwarsa.']);
        }

        if (! is_string($user->otp_code) || ! Hash::check($validated['otp'], $user->otp_code)) {
            $auditTrailService->record(
                $request,
                'OTP_INVALID',
                'auth',
                $user->id,
                [
                    'email' => $user->email,
                ]
            );

            return back()->withErrors(['otp' => 'OTP tidak valid.']);
        }

        $user->forceFill([
            'otp_verified_at' => now(),
            'otp_code' => null,
            'otp_expires_at' => null,
        ])->save();

        $auditTrailService->record(
            $request,
            'OTP_VERIFIED',
            'auth',
            $user->id,
            [
                'email' => $user->email,
            ]
        );

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function logout(Request $request, AuditTrailService $auditTrailService): RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            $auditTrailService->record(
                $request,
                'LOGOUT',
                'auth',
                $user->id,
                [
                    'email' => $user->email,
                    'role' => $user->role,
                ]
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
