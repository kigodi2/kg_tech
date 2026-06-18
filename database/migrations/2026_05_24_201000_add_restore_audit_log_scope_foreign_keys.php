<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('restore_audit_logs')) {
            return;
        }

        Schema::table('restore_audit_logs', function (Blueprint $table) {
            if (
                Schema::hasTable('regions')
                && Schema::hasColumn('restore_audit_logs', 'region_id')
                && ! $this->foreignKeyExists('restore_audit_logs', 'restore_audit_logs_region_id_foreign')
            ) {
                $table->foreign('region_id', 'restore_audit_logs_region_id_foreign')
                    ->references('id')
                    ->on('regions')
                    ->restrictOnDelete();
            }

            if (
                Schema::hasTable('districts')
                && Schema::hasColumn('restore_audit_logs', 'district_id')
                && ! $this->foreignKeyExists('restore_audit_logs', 'restore_audit_logs_district_id_foreign')
            ) {
                $table->foreign('district_id', 'restore_audit_logs_district_id_foreign')
                    ->references('id')
                    ->on('districts')
                    ->restrictOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('restore_audit_logs')) {
            return;
        }

        Schema::table('restore_audit_logs', function (Blueprint $table) {
            if ($this->foreignKeyExists('restore_audit_logs', 'restore_audit_logs_region_id_foreign')) {
                $table->dropForeign('restore_audit_logs_region_id_foreign');
            }

            if ($this->foreignKeyExists('restore_audit_logs', 'restore_audit_logs_district_id_foreign')) {
                $table->dropForeign('restore_audit_logs_district_id_foreign');
            }
        });
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
