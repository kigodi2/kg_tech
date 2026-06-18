<?php

namespace Tests\Feature;

use App\Models\DistrictCouncil;
use App\Models\MarkingCentre;
use App\Models\Region;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PsleUserManagementImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $nonAdmin;
    private Region $region;
    private DistrictCouncil $council;
    private MarkingCentre $centre;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['code' => 'admin'], ['name' => 'Administrator']);
        Role::firstOrCreate(['code' => 'mark_officer'], ['name' => 'Mark Entry Officer']);
        Role::firstOrCreate(['code' => 'reo'], ['name' => 'Regional Education Officer']);
        Role::firstOrCreate(['code' => 'centre_verifier'], ['name' => 'Marking Centre Verifier']);

        $this->admin = User::factory()->create([
            'is_admin' => true,
            'portal_role' => 'admin',
            'status' => 'active',
        ]);

        $this->nonAdmin = User::factory()->create([
            'portal_role' => 'mark_officer',
            'status' => 'active',
        ]);

        $this->region = Region::factory()->create(['name' => 'IRINGA']);
        $this->council = DistrictCouncil::create([
            'region_id' => $this->region->id,
            'code' => 'IRMC',
            'name' => 'IRINGA MC',
            'is_active' => true,
        ]);
        $this->centre = MarkingCentre::create([
            'region_id' => $this->region->id,
            'code' => 'IFUNDA',
            'name' => "IFUNDA GIRLS' SECONDARY SCHOOL",
            'status' => 'active',
        ]);
    }

    public function test_admin_can_create_one_mark_entry_officer(): void
    {
        $role = Role::where('code', 'mark_officer')->first();

        $response = $this->actingAs($this->admin)->post('/mark-entry/psle/users/create', [
            'name' => 'Single Officer',
            'email' => 'single.officer@example.test',
            'phone' => '255700000000',
            'password_mode' => 'manual',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'role_id' => $role->id,
            'region_id' => $this->region->id,
            'district_council_id' => $this->council->id,
            'marking_centre_id' => $this->centre->id,
            'status' => 'active',
            'force_password_reset' => '1',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'single.officer@example.test',
            'portal_role' => 'mark_officer',
            'region_id' => $this->region->id,
            'district_council_id' => $this->council->id,
            'marking_centre_id' => $this->centre->id,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_import_multiple_mark_entry_officers(): void
    {
        $csv = $this->csv([
            ['Officer One', 'officer.one@example.test', '255700000001', 'Mark Entry Officer', 'IRINGA', 'IRINGA MC', "IFUNDA GIRLS' SECONDARY SCHOOL", 'Password@123', 'active'],
            ['Officer Two', 'officer.two@example.test', '255700000002', 'Mark Entry Officer', 'iringa', 'iringa mc', "ifunda girls' secondary school", '', 'active'],
        ]);

        $response = $this->actingAs($this->admin)->post('/mark-entry/psle/users/import', [
            'users_csv' => $this->uploadedCsv($csv),
        ]);

        $response->assertRedirect('/mark-entry/psle?view=user-management')
            ->assertSessionHas('user_import_summary');

        $this->assertSame(2, User::where('portal_role', 'mark_officer')->where('marking_centre_id', $this->centre->id)->count());
    }

    public function test_duplicate_email_is_skipped_and_reported(): void
    {
        User::factory()->create(['email' => 'dupe@example.test']);

        $response = $this->actingAs($this->admin)->post('/mark-entry/psle/users/import', [
            'users_csv' => $this->uploadedCsv($this->csv([
                ['Duplicate User', 'dupe@example.test', '', 'Mark Entry Officer', 'IRINGA', 'IRINGA MC', "IFUNDA GIRLS' SECONDARY SCHOOL", 'Password@123', 'active'],
            ])),
        ]);

        $response->assertSessionHas('user_import_summary');
        $summary = session('user_import_summary');
        $this->assertSame(0, $summary['created']);
        $this->assertSame(1, $summary['duplicates']);
    }

    public function test_invalid_region_and_role_are_reported(): void
    {
        $response = $this->actingAs($this->admin)->post('/mark-entry/psle/users/import', [
            'users_csv' => $this->uploadedCsv($this->csv([
                ['Bad Region', 'bad.region@example.test', '', 'Mark Entry Officer', 'UNKNOWN', '', "IFUNDA GIRLS' SECONDARY SCHOOL", 'Password@123', 'active'],
                ['Bad Role', 'bad.role@example.test', '', 'Not A Role', 'IRINGA', '', "IFUNDA GIRLS' SECONDARY SCHOOL", 'Password@123', 'active'],
            ])),
        ]);

        $response->assertSessionHas('user_import_summary');
        $summary = session('user_import_summary');
        $this->assertSame(0, $summary['created']);
        $this->assertSame(2, $summary['failed']);
    }

    public function test_non_admin_cannot_import_users(): void
    {
        $response = $this->actingAs($this->nonAdmin)->post('/mark-entry/psle/users/import', [
            'users_csv' => $this->uploadedCsv($this->csv([
                ['Officer Three', 'officer.three@example.test', '', 'Mark Entry Officer', 'IRINGA', '', "IFUNDA GIRLS' SECONDARY SCHOOL", 'Password@123', 'active'],
            ])),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('users', ['email' => 'officer.three@example.test']);
    }

    public function test_imported_users_receive_correct_role_region_and_marking_centre(): void
    {
        $this->actingAs($this->admin)->post('/mark-entry/psle/users/import', [
            'users_csv' => $this->uploadedCsv($this->csv([
                ['Verifier User', 'verifier@example.test', '', 'Verifier', 'IRINGA', 'IRINGA MC', "IFUNDA GIRLS' SECONDARY SCHOOL", 'Password@123', 'inactive'],
            ])),
        ]);

        $user = User::where('email', 'verifier@example.test')->firstOrFail();

        $this->assertSame('centre_verifier', $user->portal_role);
        $this->assertSame($this->region->id, $user->region_id);
        $this->assertSame($this->council->id, $user->district_council_id);
        $this->assertSame($this->centre->id, $user->marking_centre_id);
        $this->assertSame('suspended', $user->status);
    }

    private function csv(array $rows): string
    {
        $lines = ['name,email,phone,role,region,council,marking_centre,password,status'];
        foreach ($rows as $row) {
            $handle = fopen('php://temp', 'r+');
            fputcsv($handle, $row);
            rewind($handle);
            $lines[] = rtrim(stream_get_contents($handle));
            fclose($handle);
        }

        return implode("\n", $lines) . "\n";
    }

    private function uploadedCsv(string $contents): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'psle_users_');
        file_put_contents($path, $contents);

        return new UploadedFile($path, 'users.csv', 'text/csv', null, true);
    }
}
