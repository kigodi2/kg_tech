<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_formats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_type_id')->constrained('exam_types')->restrictOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            $table->string('format_code', 100)->nullable();
            $table->string('format_name', 255);
            $table->string('version_year', 20)->nullable();
            $table->string('candidate_scope', 150)->nullable();
            $table->unsignedInteger('total_papers')->default(1);
            $table->longText('general_objectives_text')->nullable();
            $table->longText('general_competencies_text')->nullable();
            $table->longText('general_instructions')->nullable();
            $table->longText('administrative_notes')->nullable();
            $table->string('source_reference', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['exam_type_id', 'subject_id', 'is_active'], 'subject_formats_master_idx');
        });

        Schema::create('subject_format_papers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_format_id')->constrained('subject_formats')->cascadeOnDelete();
            $table->string('paper_code', 50);
            $table->unsignedInteger('paper_no');
            $table->string('paper_name', 255);
            $table->string('paper_type', 50);
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->decimal('total_marks', 8, 2)->default(0);
            $table->unsignedInteger('questions_total')->nullable();
            $table->unsignedInteger('questions_to_answer')->nullable();
            $table->boolean('has_sections')->default(false);
            $table->longText('candidate_notes')->nullable();
            $table->longText('admin_notes')->nullable();
            $table->unsignedInteger('display_order')->default(1);
            $table->timestamps();
        });

        Schema::create('subject_format_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_format_paper_id')->constrained('subject_format_papers')->cascadeOnDelete();
            $table->string('section_code', 50)->nullable();
            $table->string('section_name', 255);
            $table->longText('instructions')->nullable();
            $table->decimal('total_marks', 8, 2)->default(0);
            $table->unsignedInteger('number_of_questions')->default(0);
            $table->unsignedInteger('questions_to_answer')->nullable();
            $table->boolean('is_all_compulsory')->default(true);
            $table->unsignedInteger('display_order')->default(1);
            $table->timestamps();
        });

        Schema::create('subject_format_question_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_format_section_id')->constrained('subject_format_sections')->cascadeOnDelete();
            $table->unsignedInteger('question_no_from')->nullable();
            $table->unsignedInteger('question_no_to')->nullable();
            $table->string('question_type', 50);
            $table->unsignedInteger('items_per_question')->nullable();
            $table->decimal('marks_per_item', 8, 2)->nullable();
            $table->decimal('marks_per_question', 8, 2)->nullable();
            $table->decimal('total_marks', 8, 2)->default(0);
            $table->string('answer_mode', 50)->default('all');
            $table->boolean('is_compulsory')->default(true);
            $table->unsignedInteger('choice_count')->nullable();
            $table->unsignedInteger('display_order')->default(1);
            $table->timestamps();
        });

        Schema::create('subject_format_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_format_id')->constrained('subject_formats')->cascadeOnDelete();
            $table->foreignId('subject_format_paper_id')->nullable()->constrained('subject_format_papers')->cascadeOnDelete();
            $table->string('note_type', 60);
            $table->longText('note_text');
            $table->boolean('applies_to_candidates')->default(true);
            $table->boolean('applies_to_admins')->default(false);
            $table->unsignedInteger('display_order')->default(1);
            $table->timestamps();
        });

        Schema::create('subject_blueprints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_format_paper_id')->constrained('subject_format_papers')->cascadeOnDelete();
            $table->string('blueprint_name', 255);
            $table->unsignedInteger('total_items')->default(0);
            $table->decimal('total_weight', 8, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('subject_blueprint_topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_blueprint_id')->constrained('subject_blueprints')->cascadeOnDelete();
            $table->string('topic_name', 255);
            $table->unsignedInteger('items_count')->default(0);
            $table->decimal('percentage_weight', 8, 2)->default(0);
            $table->decimal('remembering_weight', 8, 2)->nullable();
            $table->decimal('understanding_weight', 8, 2)->nullable();
            $table->decimal('applying_weight', 8, 2)->nullable();
            $table->decimal('analysing_weight', 8, 2)->nullable();
            $table->decimal('evaluating_weight', 8, 2)->nullable();
            $table->decimal('creating_weight', 8, 2)->nullable();
            $table->unsignedInteger('display_order')->default(1);
            $table->timestamps();
        });

        Schema::create('exam_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_type_id')->constrained('exam_types')->restrictOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            $table->foreignId('subject_format_id')->constrained('subject_formats')->restrictOnDelete();
            $table->string('exam_year', 20);
            $table->string('project_code', 100)->nullable();
            $table->string('project_name', 255);
            $table->string('status', 50)->default('draft');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['exam_type_id', 'subject_id', 'status'], 'exam_projects_scope_idx');
        });

        Schema::create('exam_project_papers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_project_id')->constrained('exam_projects')->cascadeOnDelete();
            $table->foreignId('subject_format_paper_id')->constrained('subject_format_papers')->restrictOnDelete();
            $table->string('paper_code', 50);
            $table->string('paper_name', 255);
            $table->string('paper_type', 50);
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->decimal('total_marks', 8, 2)->default(0);
            $table->string('status', 50)->default('draft');
            $table->unsignedInteger('display_order')->default(1);
            $table->timestamps();
        });

        Schema::create('exam_project_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_project_paper_id')->constrained('exam_project_papers')->cascadeOnDelete();
            $table->foreignId('subject_format_section_id')->nullable()->constrained('subject_format_sections')->nullOnDelete();
            $table->string('section_code', 50)->nullable();
            $table->string('section_name', 255);
            $table->longText('instructions')->nullable();
            $table->decimal('total_marks', 8, 2)->default(0);
            $table->unsignedInteger('number_of_questions')->default(0);
            $table->unsignedInteger('questions_to_answer')->nullable();
            $table->boolean('is_all_compulsory')->default(true);
            $table->unsignedInteger('display_order')->default(1);
            $table->timestamps();
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_type_id')->constrained('exam_types')->restrictOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            $table->string('paper_type', 50)->nullable();
            $table->string('topic_name', 255);
            $table->string('subtopic_name', 255)->nullable();
            $table->string('competency_code', 100)->nullable();
            $table->string('difficulty_level', 50)->nullable();
            $table->string('question_type', 50);
            $table->string('title', 255)->nullable();
            $table->longText('question_text');
            $table->decimal('marks', 8, 2);
            $table->string('status', 50)->default('draft');
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('current_version_no')->default(1);
            $table->timestamps();
            $table->index(['subject_id', 'question_type', 'status'], 'questions_lookup_idx');
        });

        Schema::create('exam_project_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_project_section_id')->constrained('exam_project_sections')->cascadeOnDelete();
            $table->foreignId('rule_id')->nullable()->constrained('subject_format_question_rules')->nullOnDelete();
            $table->string('slot_label', 100)->nullable();
            $table->unsignedInteger('question_no')->nullable();
            $table->string('question_type', 50);
            $table->unsignedInteger('items_per_question')->nullable();
            $table->decimal('marks_per_item', 8, 2)->nullable();
            $table->decimal('marks_per_question', 8, 2)->nullable();
            $table->boolean('is_compulsory')->default(true);
            $table->string('choice_group', 100)->nullable();
            $table->unsignedInteger('display_order')->default(1);
            $table->foreignId('assigned_question_id')->nullable()->constrained('questions')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->string('option_label', 20)->nullable();
            $table->longText('option_text');
            $table->boolean('is_correct')->nullable();
            $table->string('match_group', 100)->nullable();
            $table->unsignedInteger('display_order')->default(1);
            $table->timestamps();
        });

        Schema::create('question_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->string('file_path', 500);
            $table->string('file_type', 100)->nullable();
            $table->string('caption', 255)->nullable();
            $table->unsignedInteger('display_order')->default(1);
            $table->timestamps();
        });

        Schema::create('question_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->unsignedInteger('version_no');
            $table->longText('question_text');
            $table->text('change_summary')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('question_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->unsignedInteger('estimated_minutes')->nullable();
            $table->boolean('requires_calculator')->default(false);
            $table->boolean('requires_diagram')->default(false);
            $table->boolean('requires_apparatus')->default(false);
            $table->string('blueprint_topic_label', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('paper_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_project_slot_id')->constrained('exam_project_slots')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->restrictOnDelete();
            $table->decimal('custom_marks', 8, 2)->nullable();
            $table->text('custom_instructions')->nullable();
            $table->foreignId('inserted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('marking_schemes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->string('scheme_type', 60);
            $table->decimal('total_marks', 8, 2)->default(0);
            $table->longText('answer_text')->nullable();
            $table->json('rubric_json')->nullable();
            $table->string('status', 50)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('marking_scheme_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marking_scheme_id')->constrained('marking_schemes')->cascadeOnDelete();
            $table->string('item_label', 50)->nullable();
            $table->longText('description');
            $table->decimal('marks', 8, 2)->default(0);
            $table->unsignedInteger('display_order')->default(1);
            $table->timestamps();
        });

        Schema::create('review_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->nullable()->constrained('questions')->nullOnDelete();
            $table->foreignId('exam_project_paper_id')->nullable()->constrained('exam_project_papers')->nullOnDelete();
            $table->string('comment_type', 60)->nullable();
            $table->longText('comment_text');
            $table->string('status', 50)->default('open');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('approval_logs', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 100);
            $table->unsignedBigInteger('entity_id');
            $table->string('old_status', 50)->nullable();
            $table->string('new_status', 50);
            $table->text('comment')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['entity_type', 'entity_id'], 'approval_logs_entity_idx');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('module', 100)->nullable();
            $table->string('action', 100);
            $table->foreignId('exam_year_id')->nullable()->constrained('exam_years')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 50)->nullable();
            $table->string('entity_type', 100)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('metadata')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->index(['module', 'action'], 'audit_logs_module_action_idx');
            $table->index(['entity_type', 'entity_id'], 'audit_logs_entity_idx');
        });

        Schema::create('practical_paper_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_format_paper_id')->constrained('subject_format_papers')->cascadeOnDelete();
            $table->string('variant_code', 50);
            $table->string('name', 255);
            $table->unsignedInteger('candidate_min')->nullable();
            $table->unsignedInteger('candidate_max')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('practical_apparatus_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_project_paper_id')->constrained('exam_project_papers')->cascadeOnDelete();
            $table->string('title', 255);
            $table->unsignedInteger('issued_before_days')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('practical_apparatus_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practical_apparatus_list_id')->constrained('practical_apparatus_lists')->cascadeOnDelete();
            $table->string('item_name', 255);
            $table->decimal('quantity', 8, 2)->nullable();
            $table->string('unit', 50)->nullable();
            $table->string('remarks', 255)->nullable();
            $table->unsignedInteger('display_order')->default(1);
            $table->timestamps();
        });

        Schema::create('practical_confidential_instructions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_project_paper_id');
            $table->foreign('exam_project_paper_id', 'pci_project_paper_fk')
                ->references('id')
                ->on('exam_project_papers')
                ->cascadeOnDelete();
            $table->unsignedInteger('release_hours_before')->nullable();
            $table->longText('instruction_text');
            $table->boolean('is_confidential')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practical_confidential_instructions');
        Schema::dropIfExists('practical_apparatus_items');
        Schema::dropIfExists('practical_apparatus_lists');
        Schema::dropIfExists('practical_paper_variants');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('approval_logs');
        Schema::dropIfExists('review_comments');
        Schema::dropIfExists('marking_scheme_items');
        Schema::dropIfExists('marking_schemes');
        Schema::dropIfExists('paper_questions');
        Schema::dropIfExists('question_metadata');
        Schema::dropIfExists('question_versions');
        Schema::dropIfExists('question_attachments');
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('exam_project_slots');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('exam_project_sections');
        Schema::dropIfExists('exam_project_papers');
        Schema::dropIfExists('exam_projects');
        Schema::dropIfExists('subject_blueprint_topics');
        Schema::dropIfExists('subject_blueprints');
        Schema::dropIfExists('subject_format_notes');
        Schema::dropIfExists('subject_format_question_rules');
        Schema::dropIfExists('subject_format_sections');
        Schema::dropIfExists('subject_format_papers');
        Schema::dropIfExists('subject_formats');
    }
};
