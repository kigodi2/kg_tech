<?php

namespace App\Filament\Admin\Resources\GradingProfileResource\Pages;

use App\Filament\Admin\Resources\GradingProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGradingProfile extends EditRecord
{
    protected static string $resource = GradingProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
