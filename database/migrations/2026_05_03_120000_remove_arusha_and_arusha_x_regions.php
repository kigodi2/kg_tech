<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remove legacy / duplicate regions named "Arusha" and "Arusha X" and their scoped rows.
     */
    public function up(): void
    {
        $names = ['Arusha', 'Arusha X'];
        $regionIds = DB::table('regions')->whereIn('name', $names)->pluck('id');
        if ($regionIds->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($regionIds) {
            $schoolIds = DB::table('schools')->whereIn('region_id', $regionIds)->pluck('id');

            DB::table('users')->whereIn('region_id', $regionIds)->update(['region_id' => null]);

            if ($schoolIds->isNotEmpty()) {
                DB::table('users')->whereIn('school_id', $schoolIds)->update(['school_id' => null]);
            }

            DB::table('schools')->whereIn('region_id', $regionIds)->delete();
            DB::table('districts')->whereIn('region_id', $regionIds)->delete();
            DB::table('district_councils')->whereIn('region_id', $regionIds)->delete();
            DB::table('regions')->whereIn('id', $regionIds)->delete();
        });
    }

    /**
     * Irreversible data removal.
     */
    public function down(): void
    {
    }
};
