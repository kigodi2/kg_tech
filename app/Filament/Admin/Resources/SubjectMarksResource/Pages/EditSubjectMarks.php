<?php

namespace App\Filament\Admin\Resources\SubjectMarksResource\Pages;

use App\Filament\Admin\Resources\SubjectMarksResource;
use App\Services\Results\NectaGradingService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubjectMarks extends EditRecord
{
    protected static string $resource = SubjectMarksResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $marksObtained = $data['marks_obtained'] ?? 0;
        $maxMarks = $data['max_marks'] ?? 100;
        
        // Calculate percentage
        $percentage = $maxMarks > 0 ? round(($marksObtained / $maxMarks) * 100, 2) : 0;
        
        // Calculate grade using grading service
        $gradingService = app(NectaGradingService::class);
        $grade = $gradingService->calculateGrade($percentage);

        $data['percentage'] = $percentage;
        $data['grade'] = $grade;

        return $data;
    }
}
