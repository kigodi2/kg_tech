<?php

namespace App\Filament\Admin\Resources\ExamTypeResource\Pages;

use App\Filament\Admin\Resources\ExamTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExamType extends EditRecord
{
    protected static string $resource = ExamTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
