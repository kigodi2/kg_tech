<?php

namespace App\Filament\Admin\Resources\SubjectMarksResource\Pages;

use App\Filament\Admin\Resources\SubjectMarksResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubjectMarks extends ListRecords
{
    protected static string $resource = SubjectMarksResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
