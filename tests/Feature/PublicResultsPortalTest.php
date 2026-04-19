<?php

namespace Tests\Feature;

use App\Models\ResultPortalItem;
use App\Models\ResultPortalLink;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicResultsPortalTest extends TestCase
{
    public function test_valid_token_renders_results_portal_page(): void
    {
        $token = bin2hex(random_bytes(24));

        $link = ResultPortalLink::create([
            'token_hash' => hash('sha256', $token),
            'name' => 'ACSEE 2026',
            'meta_json' => ['header_title' => 'PUBLIC RESULTS 2026 ACSEE'],
            'is_active' => true,
        ]);

        ResultPortalItem::create([
            'result_portal_link_id' => $link->id,
            'label' => 'Bugando Secondary School',
            'file_path' => 'results/2026/acsee/bugando.pdf',
            'sort_key' => 'BUGANDO SECONDARY SCHOOL',
        ]);

        $response = $this->get('/r/' . $token);

        $response->assertOk();
        $response->assertSee('BUGANDO SECONDARY SCHOOL', false);
        $response->assertSee('PUBLIC RESULTS 2026 ACSEE', false);
    }

    public function test_guest_can_open_valid_token_without_authentication(): void
    {
        $token = bin2hex(random_bytes(24));

        ResultPortalLink::create([
            'token_hash' => hash('sha256', $token),
            'name' => 'Guest Access',
            'is_active' => true,
        ]);

        $this->get('/r/' . $token)->assertOk();
    }

    public function test_full_portal_url_embedded_in_r_path_is_normalized(): void
    {
        $token = bin2hex(random_bytes(24));

        $link = ResultPortalLink::create([
            'token_hash' => hash('sha256', $token),
            'name' => 'Normalized URL',
            'meta_json' => ['header_title' => 'PUBLIC RESULTS 2026 ACSEE'],
            'is_active' => true,
        ]);

        ResultPortalItem::create([
            'result_portal_link_id' => $link->id,
            'label' => 'Mzumbe Secondary School',
            'file_path' => 'results/2026/acsee/mzumbe.pdf',
            'sort_key' => 'MZUMBE SECONDARY SCHOOL',
        ]);

        $this->get('/r/https://portal.irms.ac.tz/r/' . $token)
            ->assertOk()
            ->assertSee('MZUMBE SECONDARY SCHOOL', false);
    }

    public function test_invalid_token_returns_404(): void
    {
        $this->get('/r/invalid-token')->assertNotFound();
    }

    public function test_expired_token_returns_410(): void
    {
        $token = bin2hex(random_bytes(24));

        ResultPortalLink::create([
            'token_hash' => hash('sha256', $token),
            'name' => 'Expired',
            'is_active' => true,
            'expires_at' => now()->subHour(),
        ]);

        $this->get('/r/' . $token)->assertStatus(410);
    }

    public function test_expired_default_token_is_still_accessible(): void
    {
        $token = bin2hex(random_bytes(24));

        config()->set('services.results_portal.default_token', $token);

        ResultPortalLink::create([
            'token_hash' => hash('sha256', $token),
            'name' => 'Default Portal',
            'meta_json' => ['header_title' => 'PUBLIC RESULTS 2026 ACSEE'],
            'is_active' => true,
            'expires_at' => now()->subHour(),
        ]);

        $this->get('/r/' . $token)
            ->assertOk()
            ->assertSee('PUBLIC RESULTS 2026 ACSEE', false);
    }

    public function test_download_uses_private_disk_and_scope_checks(): void
    {
        Storage::fake('private');

        $token = bin2hex(random_bytes(24));

        $link = ResultPortalLink::create([
            'token_hash' => hash('sha256', $token),
            'name' => 'Scope Test',
            'is_active' => true,
        ]);

        $item = ResultPortalItem::create([
            'result_portal_link_id' => $link->id,
            'label' => 'Alpha School',
            'file_path' => 'results/2026/acsee/alpha.pdf',
            'sort_key' => 'ALPHA SCHOOL',
        ]);

        Storage::disk('private')->put($item->file_path, 'pdf-content');

        $response = $this->get('/r/' . $token . '/file/' . $item->id);

        $response->assertOk();
        $this->assertStringContainsString('attachment;', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('Alpha School.pdf', (string) $response->headers->get('content-disposition'));
    }
}
