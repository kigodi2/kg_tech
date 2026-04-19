<?php

namespace App\Services\ExamDevelopment;

use App\Models\ExamDevelopment\ApprovalLog;
use App\Models\ExamDevelopment\ExamProject;
use App\Models\ExamDevelopment\ExamProjectPaper;
use App\Models\ExamDevelopment\Question;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalWorkflowService
{
    public function __construct(
        protected PaperValidationService $paperValidationService,
        protected MarkingSchemeValidationService $markingSchemeValidationService,
        protected AuditLogService $auditLogService
    ) {
    }

    public function transition(Model $entity, string $newStatus, ?string $comment = null): Model
    {
        return DB::transaction(function () use ($entity, $newStatus, $comment) {
            $oldStatus = $entity->status ?? null;
            $this->guardTransition($entity, $newStatus);

            $entity->status = $newStatus;
            if ($entity instanceof ExamProject && $newStatus === ExamProject::STATUS_LOCKED) {
                $entity->locked_at = now();
            }
            if ($entity instanceof ExamProject && $newStatus === ExamProject::STATUS_PUBLISHED) {
                $entity->published_at = now();
            }
            $entity->save();

            ApprovalLog::query()->create([
                'entity_type' => $entity::class,
                'entity_id' => $entity->getKey(),
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'comment' => $comment,
                'changed_by' => auth()->id(),
                'created_at' => now(),
            ]);

            $this->auditLogService->record(
                'status-transition',
                $entity::class,
                $entity->getKey(),
                ['status' => $oldStatus],
                ['status' => $newStatus],
                ['comment' => $comment]
            );

            return $entity;
        });
    }

    protected function guardTransition(Model $entity, string $newStatus): void
    {
        if ($entity instanceof ExamProject && in_array($newStatus, [ExamProject::STATUS_APPROVED, ExamProject::STATUS_LOCKED], true)) {
            $validation = $this->paperValidationService->validateProject($entity);
            $schemeValidation = $this->markingSchemeValidationService->validateProject($entity);
            if (!$validation['is_valid'] || !$schemeValidation['is_valid']) {
                throw ValidationException::withMessages([
                    'status' => array_merge($validation['errors'], $schemeValidation['errors']),
                ]);
            }
        }

        if ($entity instanceof ExamProjectPaper && in_array($newStatus, [ExamProjectPaper::STATUS_APPROVED, ExamProjectPaper::STATUS_LOCKED], true)) {
            $validation = $this->paperValidationService->validatePaper($entity);
            if (!$validation['is_valid']) {
                throw ValidationException::withMessages(['status' => $validation['errors']]);
            }
        }

        if ($entity instanceof Question && $newStatus === Question::STATUS_APPROVED && $entity->markingSchemes()->count() === 0) {
            throw ValidationException::withMessages([
                'status' => ['Questions must have a marking scheme before approval.'],
            ]);
        }
    }
}
