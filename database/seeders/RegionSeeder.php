<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        $regions = [
            ['code' => 'ARUSHA', 'name' => 'Arusha Region', 'description' => 'Arusha Region in Tanzania'],
            ['code' => 'DAR', 'name' => 'Dar es Salaam', 'description' => 'Dar es Salaam Region'],
            ['code' => 'DODOMA', 'name' => 'Dodoma', 'description' => 'Dodoma Region'],
            ['code' => 'IRINGA', 'name' => 'Iringa', 'description' => 'Iringa Region'],
            ['code' => 'KIGALI', 'name' => 'Kigali', 'description' => 'Kigali Region'],
        ];

        foreach ($regions as $region) {
            Region::create($region);
        }
    }
}
