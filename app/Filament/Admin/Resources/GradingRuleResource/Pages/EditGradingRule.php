<?php

namespace App\Filament\Admin\Resources\GradingRuleResource\Pages;

use App\Filament\Admin\Resources\GradingRuleResource;
use Filament\Resources\Pages\EditRecord;

class EditGradingRule extends EditRecord
{
    protected static string $resource = GradingRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
