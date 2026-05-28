<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GithubAuthTest extends TestCase
{
    public function test_github_redirect_route_stores_state_and_redirects_to_github(): void
    {
        config()->set('services.github.client_id', 'github-client-id');
        config()->set('services.github.client_secret', 'github-client-secret');
        config()->set('services.github.redirect', 'http://127.0.0.1:8000/auth/github/callback');

        $response = $this->get(route('auth.github.redirect', ['email' => 'agreykigodi@gmail.com']));

        $response->assertRedirect();
        $this->assertStringStartsWith('https://github.com/login/oauth/authorize?', $response->headers->get('Location'));
        $this->assertNotEmpty(session('github_oauth_state'));
    }

    public function test_github_callback_rejects_existing_local_account_that_is_not_already_linked(): void
    {
        config()->set('services.github.client_id', 'github-client-id');
        config()->set('services.github.client_secret', 'github-client-secret');
        config()->set('services.github.redirect', 'http://127.0.0.1:8000/auth/github/callback');

        $user = User::create([
            'name' => 'IRMS User',
            'email' => 'dev@example.com',
            'password' => 'secret-pass',
            'portal_role' => 'user',
            'status' => User::STATUS_ACTIVE,
            'password_reset_required' => false,
        ]);

        Http::fake([
            'https://github.com/login/oauth/access_token' => Http::response([
                'access_token' => 'github-access-token',
                'token_type' => 'bearer',
            ]),
            'https://api.github.com/user' => Http::response([
                'id' => 77,
                'login' => 'kigodi2',
                'name' => 'Kigodi Dev',
                'email' => 'dev@example.com',
                'avatar_url' => 'https://avatars.githubusercontent.com/u/77?v=4',
            ]),
        ]);

        $this->withSession(['github_oauth_state' => 'oauth-state-token']);

        $response = $this->get(route('auth.github.callback', [
            'code' => 'oauth-code',
            'state' => 'oauth-state-token',
        ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertNull($user->fresh()->github_id);
    }

    public function test_github_callback_logs_in_user_that_is_already_linked(): void
    {
        config()->set('services.github.client_id', 'github-client-id');
        config()->set('services.github.client_secret', 'github-client-secret');
        config()->set('services.github.redirect', 'http://127.0.0.1:8000/auth/github/callback');

        $user = User::create([
            'name' => 'IRMS User',
            'email' => 'agreykigodi@gmail.com',
            'github_id' => '77',
            'github_username' => 'old-kigodi',
            'password' => 'secret-pass',
            'portal_role' => 'user',
            'status' => User::STATUS_ACTIVE,
            'password_reset_required' => false,
        ]);

        Http::fake([
            'https://github.com/login/oauth/access_token' => Http::response([
                'access_token' => 'github-access-token',
                'token_type' => 'bearer',
            ]),
            'https://api.github.com/user' => Http::response([
                'id' => 77,
                'login' => 'kigodi2',
                'name' => 'Kigodi Dev',
                'email' => 'agreykigodi@gmail.com',
                'avatar_url' => 'https://avatars.githubusercontent.com/u/77?v=4',
            ]),
        ]);

        $this->withSession(['github_oauth_state' => 'oauth-state-token']);

        $response = $this->get(route('auth.github.callback', [
            'code' => 'oauth-code',
            'state' => 'oauth-state-token',
        ]));

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($user->fresh());
        $this->assertSame('77', $user->fresh()->github_id);
        $this->assertSame('kigodi2', $user->fresh()->github_username);
    }

    public function test_github_callback_creates_a_new_user_when_no_matching_account_exists(): void
    {
        config()->set('services.github.client_id', 'github-client-id');
        config()->set('services.github.client_secret', 'github-client-secret');
        config()->set('services.github.redirect', 'http://127.0.0.1:8000/auth/github/callback');

        Http::fake([
            'https://github.com/login/oauth/access_token' => Http::response([
                'access_token' => 'github-access-token',
                'token_type' => 'bearer',
            ]),
            'https://api.github.com/user' => Http::response([
                'id' => 88,
                'login' => 'new-github-user',
                'name' => 'New GitHub User',
                'email' => 'agreykigodi@gmail.com',
                'avatar_url' => 'https://avatars.githubusercontent.com/u/88?v=4',
            ]),
        ]);

        $this->withSession(['github_oauth_state' => 'oauth-state-token']);

        $response = $this->get(route('auth.github.callback', [
            'code' => 'oauth-code',
            'state' => 'oauth-state-token',
        ]));

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'agreykigodi@gmail.com',
            'github_id' => '88',
            'github_username' => 'new-github-user',
        ]);
    }
}
