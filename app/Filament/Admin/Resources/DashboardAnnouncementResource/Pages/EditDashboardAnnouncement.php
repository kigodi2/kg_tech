<?php

namespace App\Filament\Admin\Resources\DashboardAnnouncementResource\Pages;

use App\Filament\Admin\Resources\DashboardAnnouncementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDashboardAnnouncement extends EditRecord
{
    protected static string $resource = DashboardAnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
