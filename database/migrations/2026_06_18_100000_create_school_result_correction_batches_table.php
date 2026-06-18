<?php
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('school_result_correction_batches');

        Schema::create('school_result_correction_batches', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedSmallInteger('exam_year');
            $table->string('exam_type', 20)->default('psle');
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('school_name_snapshot')->nullable();
            $table->string('status', 50)->default('open');
            $table->text('reason');
            $table->foreignId('opened_by')->constrained('users')->onDelete('cascade');
            $table->dateTime('opened_at');
            $table->foreignId('corrected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('corrected_at')->nullable();
            $table->foreignId('recalculated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('recalculated_at')->nullable();
            $table->foreignId('republished_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('republished_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['exam_year', 'exam_type', 'school_id'], 'src_batch_year_type_school_idx');
            $table->index('status', 'src_batch_status_idx');
            $table->index('opened_by', 'src_batch_opened_by_idx');
        });


        Schema::table('raw_marks', function (Blueprint $table) {
            if (!Schema::hasColumn('raw_marks', 'correction_batch_id')) {
                $table->foreignId('correction_batch_id')->nullable()->constrained('school_result_correction_batches')->nullOnDelete();
            }
            if (!Schema::hasColumn('raw_marks', 'correction_status')) {
                $table->string('correction_status')->nullable();
            }
            if (!Schema::hasColumn('raw_marks', 'correction_opened_at')) {
                $table->dateTime('correction_opened_at')->nullable();
            }
            if (!Schema::hasColumn('raw_marks', 'correction_opened_by')) {
                $table->foreignId('correction_opened_by')->nullable()->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('result_snapshots', function (Blueprint $table) {
            if (!Schema::hasColumn('result_snapshots', 'is_stale')) {
                $table->boolean('is_stale')->default(false);
            }
            if (!Schema::hasColumn('result_snapshots', 'stale_reason')) {
                $table->text('stale_reason')->nullable();
            }
            if (!Schema::hasColumn('result_snapshots', 'stale_at')) {
                $table->dateTime('stale_at')->nullable();
            }
            if (!Schema::hasColumn('result_snapshots', 'stale_by')) {
                $table->foreignId('stale_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('result_snapshots', 'correction_batch_id')) {
                $table->foreignId('correction_batch_id')->nullable()->constrained('school_result_correction_batches')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('result_snapshots', function (Blueprint $table) {
            if (Schema::hasColumn('result_snapshots', 'correction_batch_id')) {
                $table->dropForeign(['correction_batch_id']);
                $table->dropColumn('correction_batch_id');
            }
            $cols = ['is_stale', 'stale_reason', 'stale_at', 'stale_by'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('result_snapshots', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('raw_marks', function (Blueprint $table) {
            if (Schema::hasColumn('raw_marks', 'correction_batch_id')) {
                $table->dropForeign(['correction_batch_id']);
                $table->dropColumn('correction_batch_id');
            }
            if (Schema::hasColumn('raw_marks', 'correction_opened_by')) {
                $table->dropForeign(['correction_opened_by']);
                $table->dropColumn('correction_opened_by');
            }
            $cols = ['correction_status', 'correction_opened_at'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('raw_marks', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('school_result_correction_batches');
    }
};
