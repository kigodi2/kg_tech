<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mark_import_batches', function (Blueprint $table) {
            if (!Schema::hasColumn('mark_import_batches', 'submitted_by')) {
                $table->unsignedBigInteger('submitted_by')->nullable()->after('validated_at');
            }
            if (!Schema::hasColumn('mark_import_batches', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('submitted_by');
            }
            if (!Schema::hasColumn('mark_import_batches', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('submitted_at');
            }
            if (!Schema::hasColumn('mark_import_batches', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (!Schema::hasColumn('mark_import_batches', 'promoted_count')) {
                $table->unsignedInteger('promoted_count')->nullable()->after('processed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mark_import_batches', function (Blueprint $table) {
            $table->dropColumn(['submitted_by', 'submitted_at', 'approved_by', 'approved_at', 'promoted_count']);
        });
    }
};
