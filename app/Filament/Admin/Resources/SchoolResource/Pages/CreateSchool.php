<?php

namespace App\Filament\Admin\Resources\SchoolResource\Pages;

use App\Filament\Admin\Resources\SchoolResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSchool extends CreateRecord
{
    protected static string $resource = SchoolResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure SECONDARY type is enforced for CSEE/ACSEE schools
        // This will be triggered after the record is saved (see afterCreate)
        return $data;
    }

    protected function afterCreate(): void
    {
        // After the record is created, enforce exam type school type rules
        $this->record->enforceExamTypeSchoolType();
    }
}
