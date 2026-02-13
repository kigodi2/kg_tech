<?php

namespace Tests;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Seed the database with test data
     */
    public function seed($seeder = DatabaseSeeder::class)
    {
        $this->artisan('db:seed', ['--class' => $seeder]);
    }
}
