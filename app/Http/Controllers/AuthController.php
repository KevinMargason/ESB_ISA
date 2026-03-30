<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditTrailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        if (! Auth::attempt($credentials)) {
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

        $auditTrailService->record(
            $request,
            'LOGIN_SUCCESS',
            'user',
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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(['admin', 'supplier', 'kurir'])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::query()->create($validated);

        Auth::login($user);
        $request->session()->regenerate();

        $auditTrailService->record(
            $request,
            'REGISTER_SUCCESS',
            'user',
            $user->id,
            [
                'email' => $user->email,
                'role' => $user->role,
            ]
        );

        return redirect()->route('dashboard');
    }

    public function logout(Request $request, AuditTrailService $auditTrailService): RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            $auditTrailService->record(
                $request,
                'LOGOUT',
                'user',
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
