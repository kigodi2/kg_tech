<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\Region;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        $regions = Region::all();

        $schools = [
            ['code' => 'SCH001', 'name' => 'Arusha Primary School', 'school_type' => 'PRIMARY', 'education_level' => 'PRIMARY', 'region_id' => 1],
            ['code' => 'SCH002', 'name' => 'Dar Secondary School', 'school_type' => 'SECONDARY', 'education_level' => 'SECONDARY', 'region_id' => 2],
            ['code' => 'SCH003', 'name' => 'Dodoma Mixed School', 'school_type' => 'BOTH', 'education_level' => 'PRIMARY', 'region_id' => 3],
            ['code' => 'SCH004', 'name' => 'Iringa High School', 'school_type' => 'SECONDARY', 'education_level' => 'SECONDARY', 'region_id' => 4],
            ['code' => 'SCH005', 'name' => 'Kigali Primary', 'school_type' => 'PRIMARY', 'education_level' => 'PRIMARY', 'region_id' => 5],
        ];

        foreach ($schools as $school) {
            School::create($school);
        }
    }
}
