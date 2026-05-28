<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['code' => 'admin', 'name' => 'Administrator', 'description' => 'Full system access'],
            ['code' => 'registrar', 'name' => 'Registrar', 'description' => 'School registrar'],
            ['code' => 'officer', 'name' => 'District Officer', 'description' => 'District officer'],
            ['code' => 'reo', 'name' => 'Regional Education Officer', 'description' => 'Regional control of mark entry'],
            ['code' => 'centre_verifier', 'name' => 'Marking Centre Verifier', 'description' => 'Verifies batches at marking centre'],
            ['code' => 'mark_officer', 'name' => 'Mark Entry Officer', 'description' => 'Enters marks for assigned batches'],
            ['code' => 'mock_headteacher', 'name' => 'Headteacher', 'description' => 'School Headteacher'],
            ['code' => 'rao', 'name' => 'Regional Academic Officer', 'description' => 'Regional Academic Officer'],
            ['code' => 'dao', 'name' => 'District Academic Officer', 'description' => 'District Academic Officer'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['code' => $role['code']], $role);
        }
    }
}
