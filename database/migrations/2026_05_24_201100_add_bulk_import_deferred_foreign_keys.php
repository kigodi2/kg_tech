<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bulk_imports') && Schema::hasTable('schools')) {
            Schema::table('bulk_imports', function (Blueprint $table) {
                if (! $this->foreignKeyExists('bulk_imports', 'bulk_imports_school_id_foreign')) {
                    $table->foreign('school_id', 'bulk_imports_school_id_foreign')
                        ->references('id')
                        ->on('schools')
                        ->cascadeOnDelete();
                }
            });
        }

        if (Schema::hasTable('bulk_import_files') && Schema::hasTable('subjects')) {
            Schema::table('bulk_import_files', function (Blueprint $table) {
                if (! $this->foreignKeyExists('bulk_import_files', 'bulk_import_files_subject_id_foreign')) {
                    $table->foreign('subject_id', 'bulk_import_files_subject_id_foreign')
                        ->references('id')
                        ->on('subjects')
                        ->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bulk_import_files') && $this->foreignKeyExists('bulk_import_files', 'bulk_import_files_subject_id_foreign')) {
            Schema::table('bulk_import_files', fn (Blueprint $table) => $table->dropForeign('bulk_import_files_subject_id_foreign'));
        }

        if (Schema::hasTable('bulk_imports') && $this->foreignKeyExists('bulk_imports', 'bulk_imports_school_id_foreign')) {
            Schema::table('bulk_imports', fn (Blueprint $table) => $table->dropForeign('bulk_imports_school_id_foreign'));
        }
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            return DB::table('information_schema.TABLE_CONSTRAINTS')
                ->where('CONSTRAINT_SCHEMA', $connection->getDatabaseName())
                ->where('TABLE_NAME', $table)
                ->where('CONSTRAINT_NAME', $constraint)
                ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
                ->exists();
        }

        return false;
    }
};
