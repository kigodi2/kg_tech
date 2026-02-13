<?php

namespace App\Filament\Admin\Resources\CandidateResultResource\Pages;

use App\Filament\Admin\Resources\CandidateResultResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCandidateResult extends EditRecord
{
    protected static string $resource = CandidateResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
