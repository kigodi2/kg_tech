<?php

namespace App\Filament\Admin\Resources\ExamTypeResource\Pages;

use App\Filament\Admin\Resources\ExamTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExamTypes extends ListRecords
{
    protected static string $resource = ExamTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
