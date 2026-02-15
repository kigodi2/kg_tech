<?php

namespace Tests;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Seed the database with test data
     */
    public function seed($seeder = DatabaseSeeder::class)
    {
        $this->artisan('db:seed', ['--class' => $seeder]);
    }
}
