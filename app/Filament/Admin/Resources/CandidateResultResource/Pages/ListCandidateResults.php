<?php

namespace App\Filament\Admin\Resources\CandidateResultResource\Pages;

use App\Filament\Admin\Resources\CandidateResultResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCandidateResults extends ListRecords
{
    protected static string $resource = CandidateResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
