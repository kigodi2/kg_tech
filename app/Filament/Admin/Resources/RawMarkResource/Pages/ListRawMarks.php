<?php

namespace App\Filament\Admin\Resources\RawMarkResource\Pages;

use App\Filament\Admin\Resources\RawMarkResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRawMarks extends ListRecords
{
    protected static string $resource = RawMarkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
