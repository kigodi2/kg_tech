<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\School;
use App\Models\DistrictCouncil;
use App\Models\Region;
use App\Models\District;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class MockPortalAuthController extends Controller
{
    private const DAO_ACCOUNT_LIMIT_PER_DISTRICT = 5;

    private const PORTAL_ROLES = [
        'mock_secretariat',
        'mock_rao',
        'mock_dao',
        'mock_headteacher',
    ];

    /**
     * Single entry point: https://irms.ac.tz/mock-portal
     * Authenticated users are sent to their dashboard; guests see the welcome page.
     */
    public function welcome()
    {
        if (Auth::check()) {
            $role = Auth::user()->portal_role;
            if ($role === 'mock_secretariat') return redirect()->route('mock-portal.secretariat.dashboard');
            if ($role === 'mock_rao')         return redirect()->route('mock-portal.rao.dashboard');
            if ($role === 'mock_dao')         return redirect()->route('mock-portal.dao.dashboard');
            if ($role === 'mock_headteacher') return redirect()->route('mock-portal.school.dashboard');
            // For system admins, we let them see the welcome page so they can preview the portal
            if (Auth::user()->isAdmin()) return view('mock-portal.auth.welcome');

            return redirect()->to(AuthController::redirectPathForUser(Auth::user()));
        }

        return view('mock-portal.auth.welcome');
    }

    public function checkSchool($code)
    {
        $school = \App\Models\School::where('code', $code)
            ->orWhere('registration_number', $code)
            ->first();

        if (!$school) {
            return response()->json([
                'found'   => false,
                'message' => 'School not found. Please verify the Centre Number.'
            ]);
        }

        if ($school->school_type !== 'PRIMARY') {
            return response()->json([
                'found'   => false,
                'message' => 'This is a ' . $school->school_type . ' school. This portal is for PRIMARY schools only.'
            ]);
        }

        return response()->json([
            'found' => true,
            'name'  => $school->name,
            'ownership' => $school->ownership
        ]);
    }

    public function getDistricts($regionId)
    {
        $region = Region::find($regionId);
        if (!$region) return response()->json([]);

        $districts = District::query()
            ->where('region_id', $region->id)
            ->whereHas('schools', function ($q) {
                $q->whereIn('school_type', ['PRIMARY', 'BOTH']);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($districts);
    }

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->to(AuthController::redirectPathForUser(Auth::user()));
        }

        return view('mock-portal.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        Log::info('Mock login attempt', [
            'email' => $credentials['email'],
            'path' => $request->path(),
        ]);

        Log::info('POST mock login reached', [
            'email' => $credentials['email'],
        ]);

        $remember = $request->boolean('remember');
        $authenticated = Auth::attempt($credentials, $remember);

        Log::info('Auth attempt result', [
            'email' => $credentials['email'],
            'success' => $authenticated,
        ]);

        if ($authenticated) {
            $request->session()->regenerate();
            $user = Auth::user()->loadMissing('role');

            if ($message = $this->mockPortalLoginBlockReason($user)) {
                Log::warning('Mock login blocked after successful authentication', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'portal_role' => $user->portal_role,
                    'role_code' => $user->role?->code,
                    'status' => $user->status,
                    'reason' => $message,
                ]);

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('mock-portal.login')
                    ->withErrors(['email' => $message])
                    ->withInput($request->only('email'));
            }

            $user->update(['last_login_at' => now()]);

            $targetUrl = AuthController::redirectPathForUser($user);

            Log::info('Mock login success', [
                'user_id' => $user->id,
                'email' => $user->email,
                'portal_role' => $user->portal_role,
                'role_code' => $user->role?->code,
                'role_name' => $user->role?->name,
            ]);

            Log::info('Redirecting after mock login', [
                'user_id' => $user->id,
                'target' => $targetUrl,
            ]);

            $request->session()->forget('url.intended');

            return redirect()->to($targetUrl);
        }

        Log::warning('Mock login failed', [
            'email' => $credentials['email'],
            'path' => $request->path(),
        ]);

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    private function mockPortalLoginBlockReason(User $user): ?string
    {
        if ($user->status === User::STATUS_SUSPENDED) {
            return 'Your account has been suspended. Please contact the administrator.';
        }

        if (!in_array($user->portal_role, self::PORTAL_ROLES, true)) {
            return null;
        }

        if ($user->portal_role === 'mock_rao' && !$user->region_id) {
            return 'Your RAO account is not assigned to a region. Please contact the administrator.';
        }

        if ($user->portal_role === 'mock_dao' && !$user->district_council_id) {
            return 'Your DAO account is not assigned to a district council. Please contact the administrator.';
        }

        if ($user->portal_role === 'mock_headteacher' && !$user->school_id) {
            return 'Your Headteacher account is not linked to a school. Please contact your DAO.';
        }

        return null;
    }

    public function expired()
    {
        return view('mock-portal.auth.expired', [
            'registrationDeadline' => $this->mockRegistrationDeadline()->format('d M Y'),
        ]);
    }

    private function mockRegistrationDeadline(): Carbon
    {
        return Carbon::parse('2026-04-20')->addDays(31)->startOfDay();
    }

    public static function mockRegistrationExpired(): bool
    {
        return now()->greaterThan(Carbon::parse('2026-04-20')->addDays(31)->startOfDay());
    }

    public function showRegister()
    {
        // All regions (RAOs can be assigned to any region, including ones still being set up)
        $regions = Region::where('name', 'NOT LIKE', '%UNASSIGNED%')
            ->orderBy('name')
            ->get();

        return view('mock-portal.auth.register', compact('regions'));
    }

    public function register(Request $request)
    {
        try {
            $request->validate([
                'name'        => 'required|string|max:255',
                'email'       => 'required|string|email|max:255|unique:users',
                'password'    => 'required|string|min:6',
                'portal_role' => 'required|in:mock_secretariat,mock_rao,mock_dao,mock_headteacher',
                'code'        => 'required_if:portal_role,mock_headteacher|string|nullable',
                'region_id'   => 'required_if:portal_role,mock_rao,mock_dao|integer|nullable|exists:regions,id',
                'district_id' => 'required_if:portal_role,mock_dao|integer|nullable|exists:districts,id',
            ]);

            $role = $request->portal_role;
            $code = $request->code;

            $school_id           = null;
            $district_council_id = null;
            $region_id           = null;

            if ($role === 'mock_secretariat') {
                // Secretariat: zonal-wide follow-up access, no scoped entity required.
            } elseif ($role === 'mock_rao') {
                // RAO: region-level access
                $region = Region::find($request->region_id);
                if (!$region) {
                    return back()->withErrors(['region_id' => 'Region not found.'])->withInput();
                }

                $region_id = $region->id;

            } elseif ($role === 'mock_dao') {
                $district = District::find($request->district_id);
                if (!$district) {
                    return back()->withErrors(['district_id' => 'District not found.'])->withInput();
                }

                $council = DistrictCouncil::firstOrCreate(
                    [
                        'name' => $district->name,
                        'region_id' => $district->region_id,
                    ],
                    [
                        'code' => $district->code,
                        'description' => $district->description,
                        'is_active' => true,
                    ]
                );

                // Align schools with the DAO council scope used elsewhere in the mock portal.
                School::where('district_id', $district->id)
                    ->where(function ($query) use ($council) {
                        $query->whereNull('council_id')
                            ->orWhere('council_id', '!=', $council->id);
                    })
                    ->update(['council_id' => $council->id]);

                $district_council_id = $council->id;

                $existingDaoCountForDistrict = User::where('district_council_id', $council->id)
                    ->where('portal_role', 'mock_dao')
                    ->count();

                if ($existingDaoCountForDistrict >= self::DAO_ACCOUNT_LIMIT_PER_DISTRICT) {
                    return back()->withErrors([
                        'district_id' => 'Only 5 DAO accounts are allowed for this district.',
                    ])->withInput();
                }
                
                $region_id = $district->region_id;
            } else {
                $school = School::where('code', $code)->orWhere('registration_number', $code)->where('school_type', 'PRIMARY')->first();
                if (!$school) return back()->withErrors(['code' => 'School not found.'])->withInput();
                
                $existing = User::where('school_id', $school->id)->where('portal_role', 'mock_headteacher')->first();
                if ($existing) return back()->withErrors(['code' => 'This school is already registered.'])->withInput();
                
                $school_id = $school->id;
                $district_council_id = $school->council_id;
                $region_id = $school->region_id;
            }

            $user = User::create([
                'name'                => $request->name,
                'email'               => $request->email,
                'password'            => Hash::make($request->password),
                'portal_role'         => $role,
                'school_id'           => $school_id,
                'district_council_id' => $district_council_id,
                'region_id'           => $region_id,
                'status'              => User::STATUS_ACTIVE,
            ]);

            Auth::login($user);

            if ($role === 'mock_secretariat') return redirect()->route('mock-portal.secretariat.dashboard');
            if ($role === 'mock_rao')         return redirect()->route('mock-portal.rao.dashboard');
            if ($role === 'mock_dao')         return redirect()->route('mock-portal.dao.dashboard');
            return redirect()->route('mock-portal.school.dashboard');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Registration Failed: ' . $e->getMessage()])->withInput();
        }
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)
            ->whereIn('portal_role', self::PORTAL_ROLES)
            ->first();

        if (!$user) {
            return back()
                ->withErrors(['email' => 'We could not find a mock portal account with that email address.'])
                ->withInput([
                    'email' => $request->email,
                    'auth_view' => 'forgot',
                ]);
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => Hash::make($token),
                'created_at' => Carbon::now()
            ]
        );

        $resetLink = route('mock-portal.password.reset', ['token' => $token, 'email' => $user->email]);

        try {
            Mail::raw(
                "Use the link below to reset your Mock TASIDO 2026 portal password:\n\n{$resetLink}\n\nThis link will expire in 60 minutes.",
                function ($message) use ($user) {
                    $message->to($user->email)
                        ->subject('Mock TASIDO 2026 Password Reset');
                }
            );
        } catch (\Throwable $exception) {
            \Log::warning('Mock portal password reset email could not be delivered.', [
                'email' => $user->email,
                'error' => $exception->getMessage(),
                'reset_link' => $resetLink,
            ]);

            return back()
                ->with('status', app()->environment(['local', 'testing'])
                    ? 'Reset link generated. Email delivery is unavailable in this environment.'
                    : 'Reset link generated, but email delivery is currently unavailable. Please contact the administrator.')
                ->with('mock_portal_auth_view', 'forgot')
                ->with('mock_portal_reset_link', app()->environment(['local', 'testing']) ? $resetLink : null)
                ->withInput([
                    'email' => $user->email,
                    'auth_view' => 'forgot',
                ]);
        }

        return back()
            ->with('status', 'We have e-mailed your password reset link!')
            ->with('mock_portal_auth_view', 'forgot')
            ->withInput([
                'email' => $user->email,
                'auth_view' => 'forgot',
            ]);
    }

    public function showResetForm(Request $request, $token = null)
    {
        return view('mock-portal.auth.login', [
            'token' => $token,
            'email' => $request->email,
            'showResetForm' => true
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
            return back()->withErrors(['email' => 'Invalid token or email.']);
        }

        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            return back()->withErrors(['email' => 'This reset link has expired.']);
        }

        $user = User::where('email', $request->email)
            ->whereIn('portal_role', self::PORTAL_ROLES)
            ->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Mock portal user not found.']);
        }

        $user->update(['password' => Hash::make($request->password)]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('mock-portal.login')->with('status', 'Your password has been reset! Please login with your new password.');
    }
}
