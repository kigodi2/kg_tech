<?php

namespace App\Filament\Admin\Resources\DistrictCouncilResource\Pages;

use App\Filament\Admin\Resources\DistrictCouncilResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDistrictCouncils extends ListRecords
{
    protected static string $resource = DistrictCouncilResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
