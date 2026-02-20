<?php

namespace App\Filament\Admin\Resources\GradingProfileResource\Pages;

use App\Filament\Admin\Resources\GradingProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewGradingProfile extends ViewRecord
{
    protected static string $resource = GradingProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
