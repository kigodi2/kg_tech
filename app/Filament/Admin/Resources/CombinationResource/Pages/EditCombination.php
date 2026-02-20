<?php

namespace App\Filament\Admin\Resources\CombinationResource\Pages;

use App\Filament\Admin\Resources\CombinationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCombination extends EditRecord
{
    protected static string $resource = CombinationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
