<?php

namespace App\Filament\Admin\Resources\DistrictCouncilResource\Pages;

use App\Filament\Admin\Resources\DistrictCouncilResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDistrictCouncil extends ViewRecord
{
    protected static string $resource = DistrictCouncilResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
