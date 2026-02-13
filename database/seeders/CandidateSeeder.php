<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\School;
use Illuminate\Database\Seeder;

class CandidateSeeder extends Seeder
{
    public function run(): void
    {
        $schools = School::all();
        $firstNames = ['John', 'Mary', 'James', 'Patricia', 'Robert', 'Jennifer', 'Michael', 'Linda'];
        $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis'];
        $genders = ['M', 'F'];

        $candidateId = 1;
        foreach ($schools as $school) {
            for ($i = 0; $i < 10; $i++) {
                Candidate::create([
                    'school_id' => $school->id,
                    'candidate_id' => 'C' . str_pad($candidateId++, 6, '0', STR_PAD_LEFT),
                    'first_name' => $firstNames[array_rand($firstNames)],
                    'last_name' => $lastNames[array_rand($lastNames)],
                    'gender' => $genders[array_rand($genders)],
                    'date_of_birth' => fake()->date('Y-m-d', '2010-01-01'),
                ]);
            }
        }
    }
}
