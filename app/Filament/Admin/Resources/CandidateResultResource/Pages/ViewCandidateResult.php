<?php

namespace App\Filament\Admin\Resources\CandidateResultResource\Pages;

use App\Filament\Admin\Resources\CandidateResultResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCandidateResult extends ViewRecord
{
    protected static string $resource = CandidateResultResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
