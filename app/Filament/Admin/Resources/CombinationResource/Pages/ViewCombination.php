<?php

namespace App\Filament\Admin\Resources\CombinationResource\Pages;

use App\Filament\Admin\Resources\CombinationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCombination extends ViewRecord
{
    protected static string $resource = CombinationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
