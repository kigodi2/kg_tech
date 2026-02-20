<?php

namespace App\Filament\Admin\Resources\GradingRuleResource\Pages;

use App\Filament\Admin\Resources\GradingRuleResource;
use Filament\Resources\Pages\ViewRecord;

class ViewGradingRule extends ViewRecord
{
  protected static string $resource = GradingRuleResource::class;

  protected function getHeaderActions(): array
  {
    return [
      \Filament\Actions\EditAction::make(),
      \Filament\Actions\DeleteAction::make(),
    ];
  }
}
