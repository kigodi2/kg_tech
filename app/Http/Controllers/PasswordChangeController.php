<?php

namespace App\Http\Controllers;

use App\Models\GovernanceAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PasswordChangeController extends Controller
{
    /**
     * Show forced password change form
     */
    public function showChangeRequired()
    {
        if (!auth()->check() || !auth()->user()->password_reset_required) {
            return redirect('/');
        }

        return view('auth.force-password-change', [
            'user' => auth()->user(),
        ]);
    }

    /**
     * Handle password change
     */
    public function updateRequired(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        $user = auth()->user();

        // Update password
        $user->update([
            'password' => Hash::make($validated['password']),
            'password_reset_required' => false,
        ]);

        // Log password change
        GovernanceAuditLog::log(
            GovernanceAuditLog::ACTION_PASSWORD_CHANGED,
            userId: $user->id,
            adminId: null,
            data: [
                'user_initiated' => true,
                'changed_at' => now()->toIso8601String(),
            ]
        );

        return redirect('/')->with('success', 'Password changed successfully. Welcome to IRMS!');
    }
}
