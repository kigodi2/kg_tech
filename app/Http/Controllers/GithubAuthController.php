<?php

namespace App\Http\Controllers;

use App\Models\GovernanceAuditLog;
use App\Models\User;
use App\Services\SecurityAlertService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GithubAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $email = trim(strtolower((string) $request->query('email', '')));
        if ($email !== 'agreykigodi@gmail.com') {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'GitHub login is restricted to the system administrator.']);
        }

        $clientId = (string) config('services.github.client_id');
        $redirectUri = (string) config('services.github.redirect');

        if ($clientId === '' || $redirectUri === '') {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'GitHub login is not configured yet.']);
        }

        $state = Str::random(40);
        $request->session()->put('github_oauth_state', $state);

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => 'read:user user:email',
            'state' => $state,
            'allow_signup' => 'true',
        ]);

        return redirect()->away('https://github.com/login/oauth/authorize?' . $query);
    }

    public function callback(Request $request): RedirectResponse
    {
        $expectedState = (string) $request->session()->pull('github_oauth_state', '');
        $incomingState = (string) $request->query('state', '');

        if ($expectedState === '' || !hash_equals($expectedState, $incomingState)) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'GitHub authentication could not be verified. Please try again.']);
        }

        if ($request->filled('error')) {
            $message = (string) $request->query('error_description', 'GitHub login was cancelled or denied.');

            return redirect()
                ->route('login')
                ->withErrors(['email' => $message]);
        }

        $code = (string) $request->query('code', '');
        if ($code === '') {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'GitHub did not return an authorization code.']);
        }

        try {
            $accessToken = $this->exchangeCodeForToken($code);
            $githubUser = $this->fetchGithubUser($accessToken);
            $githubEmail = $this->resolveGithubEmail($accessToken, $githubUser);

            if ($githubEmail === null) {
                return redirect()
                    ->route('login')
                    ->withErrors(['email' => 'Your GitHub account does not expose an email address that IRMS can use.']);
            }

            // Enforce administrator-only restriction on Callback level
            if (trim(strtolower($githubEmail)) !== 'agreykigodi@gmail.com') {
                GovernanceAuditLog::log(
                    GovernanceAuditLog::ACTION_LOGIN_FAILED,
                    userId: null,
                    adminId: null,
                    data: [
                        'email' => $githubEmail,
                        'reason' => 'github_access_restricted_to_admin',
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]
                );

                return redirect()
                    ->route('login')
                    ->withErrors(['email' => 'GitHub login is restricted to the system administrator.']);
            }

            $githubId = (string) $githubUser['id'];
            if ($githubId === '' || $githubUser['login'] === '') {
                throw new \RuntimeException('GitHub user profile is incomplete.');
            }

            $user = DB::transaction(function () use ($githubUser, $githubId, $githubEmail) {
                $user = User::query()
                    ->where('github_id', $githubId)
                    ->first();

                if ($user === null) {
                    $existingEmailUser = User::query()
                        ->where('email', $githubEmail)
                        ->first();

                    if ($existingEmailUser !== null) {
                        return null;
                    }

                    $user = User::create([
                        'name' => $githubUser['name'] ?: $githubUser['login'],
                        'email' => $githubEmail,
                        'password' => Str::random(40),
                        'portal_role' => 'user',
                        'status' => User::STATUS_ACTIVE,
                        'password_reset_required' => false,
                    ]);
                }

                $user->forceFill([
                    'name' => $user->name ?: ($githubUser['name'] ?: $githubUser['login']),
                    'github_id' => $githubId,
                    'github_username' => $githubUser['login'],
                    'github_avatar' => $githubUser['avatar_url'],
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();

                return $user;
            });

            if ($user === null) {
                GovernanceAuditLog::log(
                    GovernanceAuditLog::ACTION_LOGIN_FAILED,
                    userId: null,
                    adminId: null,
                    data: [
                        'email' => $githubEmail,
                        'reason' => 'github_account_not_linked',
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]
                );

                return redirect()
                    ->route('login')
                    ->withErrors([
                        'email' => 'A local IRMS account already exists for this email. Sign in with your password first and ask an administrator to link GitHub access.',
                    ]);
            }

            return app(AuthController::class)->finalizeSuccessfulLogin($request, $user);
        } catch (RequestException $exception) {
            Log::warning('GitHub OAuth request failed.', [
                'message' => $exception->getMessage(),
                'status' => $exception->response?->status(),
            ]);
        } catch (\Throwable $exception) {
            Log::error('GitHub OAuth callback failed.', [
                'message' => $exception->getMessage(),
            ]);
        }

        GovernanceAuditLog::log(
            GovernanceAuditLog::ACTION_LOGIN_FAILED,
            userId: null,
            adminId: null,
            data: [
                'reason' => 'github_oauth_failed',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        SecurityAlertService::logFailedLogin('github-oauth', 'github_oauth_failed');

        return redirect()
            ->route('login')
            ->withErrors(['email' => 'GitHub login could not be completed right now.']);
    }

    private function exchangeCodeForToken(string $code): string
    {
        $response = Http::asForm()
            ->acceptJson()
            ->post('https://github.com/login/oauth/access_token', [
                'client_id' => config('services.github.client_id'),
                'client_secret' => config('services.github.client_secret'),
                'code' => $code,
                'redirect_uri' => config('services.github.redirect'),
            ])
            ->throw()
            ->json();

        $accessToken = $response['access_token'] ?? null;
        if (!is_string($accessToken) || $accessToken === '') {
            throw new \RuntimeException('GitHub access token was not returned.');
        }

        return $accessToken;
    }

    /**
     * @return array{id:int|string,login:string,name:?string,avatar_url:?string,email:?string}
     */
    private function fetchGithubUser(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->get('https://api.github.com/user')
            ->throw()
            ->json();

        return [
            'id' => $response['id'] ?? '',
            'login' => (string) ($response['login'] ?? ''),
            'name' => isset($response['name']) ? (string) $response['name'] : null,
            'avatar_url' => isset($response['avatar_url']) ? (string) $response['avatar_url'] : null,
            'email' => isset($response['email']) ? (string) $response['email'] : null,
        ];
    }

    private function resolveGithubEmail(string $accessToken, array $githubUser): ?string
    {
        $primaryEmail = $githubUser['email'] ?? null;
        if (is_string($primaryEmail) && $primaryEmail !== '') {
            return Str::lower($primaryEmail);
        }

        $emails = Http::withToken($accessToken)
            ->acceptJson()
            ->get('https://api.github.com/user/emails')
            ->throw()
            ->json();

        foreach ($emails as $email) {
            if (($email['primary'] ?? false) && ($email['verified'] ?? false) && !empty($email['email'])) {
                return Str::lower((string) $email['email']);
            }
        }

        foreach ($emails as $email) {
            if (($email['verified'] ?? false) && !empty($email['email'])) {
                return Str::lower((string) $email['email']);
            }
        }

        return null;
    }
}
