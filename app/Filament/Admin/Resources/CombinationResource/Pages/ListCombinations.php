<?php

namespace App\Filament\Admin\Resources\CombinationResource\Pages;

use App\Filament\Admin\Resources\CombinationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCombinations extends ListRecords
{
    protected static string $resource = CombinationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
