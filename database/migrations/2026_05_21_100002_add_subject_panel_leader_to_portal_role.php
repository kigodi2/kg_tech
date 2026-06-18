<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add 'subject_panel_leader' to the portal_role column.
     * The column is a VARCHAR/string, so no enum alteration is needed.
     * This migration just serves as documentation of the new allowed value.
     */
    public function up(): void
    {
        // The portal_role column is a plain string/varchar in this project.
        // No structural change needed — the new value is enforced at the application layer.
        // Seed the role code into the roles table for the system role lookup.
        DB::table('roles')->insertOrIgnore([
            'code'        => 'subject_panel_leader',
            'name'        => 'Subject Panel Leader',
            'description' => 'Reviews and verifies marks entered by Mark Entry Officers for an assigned PSLE subject.',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('roles')->where('code', 'subject_panel_leader')->delete();
    }
};
