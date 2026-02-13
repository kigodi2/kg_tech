<?php

namespace App\Filament\Admin\Resources\ExamYearResource\Pages;

use App\Filament\Admin\Resources\ExamYearResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExamYear extends EditRecord
{
    protected static string $resource = ExamYearResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
