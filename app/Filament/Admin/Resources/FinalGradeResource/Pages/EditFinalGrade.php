<?php

namespace App\Filament\Admin\Resources\FinalGradeResource\Pages;

use App\Filament\Admin\Resources\FinalGradeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFinalGrade extends EditRecord
{
    protected static string $resource = FinalGradeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
