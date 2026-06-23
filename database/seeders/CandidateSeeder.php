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
                $firstName = $firstNames[array_rand($firstNames)];
                $lastName = $lastNames[array_rand($lastNames)];
                Candidate::create([
                    'school_id' => $school->id,
                    'candidate_id' => 'C' . str_pad($candidateId++, 6, '0', STR_PAD_LEFT),
                    'full_name' => $firstName . ' ' . $lastName,
                    'gender' => $genders[array_rand($genders)],
                ]);
            }
        }
    }
}
