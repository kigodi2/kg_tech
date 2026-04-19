<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bulk_uploads')) {
            Schema::create('bulk_uploads', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('exam_year_id')->nullable();
                $table->unsignedBigInteger('region_id')->nullable();
                $table->unsignedBigInteger('district_id')->nullable();
                $table->string('upload_type', 40)->default('district_zip');
                $table->string('original_filename', 500)->nullable();
                $table->string('zip_hash', 64)->nullable();
                $table->unsignedBigInteger('zip_size')->nullable();
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->timestamp('uploaded_at')->nullable();
                $table->string('status', 30)->default('validated'); // validated|rejected|committed
                $table->string('duplicate_status', 40)->default('new'); // new|dup_zip|has_dup_files|has_dup_rows
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['exam_year_id', 'district_id']);
                $table->index(['zip_hash']);
                $table->index(['upload_type']);
            });
        }

        if (!Schema::hasTable('bulk_upload_files')) {
            Schema::create('bulk_upload_files', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('bulk_upload_id');
                $table->string('filename', 500);
                $table->string('file_hash', 64)->nullable();
                $table->json('derived_scope')->nullable(); // school_id, subject_id, paper_code
                $table->unsignedBigInteger('duplicate_of_file_id')->nullable();
                $table->string('status', 30)->default('new');
                $table->timestamps();

                $table->index(['bulk_upload_id']);
                $table->index(['file_hash']);
                $table->foreign('bulk_upload_id')->references('id')->on('bulk_uploads')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('mark_import_batches') && !Schema::hasColumn('mark_import_batches', 'superseded_by_bulk_upload_id')) {
            Schema::table('mark_import_batches', function (Blueprint $table) {
                $table->unsignedBigInteger('superseded_by_bulk_upload_id')->nullable()->after('resubmitted_from_batch_id');
                $table->index('superseded_by_bulk_upload_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('mark_import_batches') && Schema::hasColumn('mark_import_batches', 'superseded_by_bulk_upload_id')) {
            Schema::table('mark_import_batches', function (Blueprint $table) {
                $table->dropIndex(['superseded_by_bulk_upload_id']);
                $table->dropColumn('superseded_by_bulk_upload_id');
            });
        }

        Schema::dropIfExists('bulk_upload_files');
        Schema::dropIfExists('bulk_uploads');
    }
};

