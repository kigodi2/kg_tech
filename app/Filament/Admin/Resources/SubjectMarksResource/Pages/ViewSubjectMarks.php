<?php

namespace App\Filament\Admin\Resources\SubjectMarksResource\Pages;

use App\Filament\Admin\Resources\SubjectMarksResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSubjectMarks extends ViewRecord
{
    protected static string $resource = SubjectMarksResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
