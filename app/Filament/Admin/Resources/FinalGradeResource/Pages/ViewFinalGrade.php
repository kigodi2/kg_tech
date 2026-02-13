<?php

namespace App\Filament\Admin\Resources\FinalGradeResource\Pages;

use App\Filament\Admin\Resources\FinalGradeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewFinalGrade extends ViewRecord
{
    protected static string $resource = FinalGradeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
