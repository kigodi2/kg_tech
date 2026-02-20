<?php

namespace App\Filament\Admin\Resources\DistrictCouncilResource\Pages;

use App\Filament\Admin\Resources\DistrictCouncilResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDistrictCouncil extends EditRecord
{
    protected static string $resource = DistrictCouncilResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
