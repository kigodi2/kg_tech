<?php

namespace App\Filament\Admin\Resources\GradingProfileResource\Pages;

use App\Filament\Admin\Resources\GradingProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGradingProfiles extends ListRecords
{
    protected static string $resource = GradingProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
