<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\GovernanceAuditLog;
use App\Services\SecurityAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Attempt login
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $request->session()->regenerate();

            // Check if user is suspended
            if ($user->status === User::STATUS_SUSPENDED) {
                Auth::logout();
                $request->session()->invalidate();

                // Log failed login (suspended account)
                GovernanceAuditLog::log(
                    GovernanceAuditLog::ACTION_LOGIN_FAILED,
                    userId: $user->id,
                    adminId: null,
                    data: [
                        'reason' => 'account_suspended',
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]
                );

                return back()->withErrors([
                    'email' => 'Your account has been suspended. Contact an administrator.',
                ]);
            }

            // Update last login timestamp
            $user->update(['last_login_at' => now()]);

            // Log successful login
            GovernanceAuditLog::log(
                GovernanceAuditLog::ACTION_LOGIN_SUCCESSFUL,
                userId: $user->id,
                adminId: null,
                data: [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]
            );

            return redirect()->intended('/dashboard');
        }

        // Log failed login attempt
        $email = $credentials['email'] ?? 'unknown';
        GovernanceAuditLog::log(
            GovernanceAuditLog::ACTION_LOGIN_FAILED,
            userId: null,
            adminId: null,
            data: [
                'email' => $email,
                'reason' => 'invalid_credentials',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        // Check for suspicious login attempts
        SecurityAlertService::logFailedLogin($email, 'invalid_credentials');

        return back()->withErrors([
            'email' => 'Invalid credentials',
        ]);
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);
        return redirect('/dashboard');
    }

    public function logout(Request $request)
    {
        if (auth()->check()) {
            $user = auth()->user();

            // Log logout
            GovernanceAuditLog::log(
                GovernanceAuditLog::ACTION_LOGIN_FAILED, // Reusing for logout (or create new constant)
                userId: $user->id,
                adminId: null,
                data: [
                    'event' => 'logout',
                ]
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
