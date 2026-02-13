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
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['code' => $role['code']], $role);
        }
    }
}
