<?php

namespace App\Filament\Admin\Resources\ExamYearResource\Pages;

use App\Filament\Admin\Resources\ExamYearResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExamYears extends ListRecords
{
    protected static string $resource = ExamYearResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
