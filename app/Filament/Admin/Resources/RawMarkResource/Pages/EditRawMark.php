<?php

namespace App\Filament\Admin\Resources\RawMarkResource\Pages;

use App\Filament\Admin\Resources\RawMarkResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRawMark extends EditRecord
{
    protected static string $resource = RawMarkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
