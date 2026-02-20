<?php

namespace App\Filament\Admin\Resources\GradingRuleResource\Pages;

use App\Filament\Admin\Resources\GradingRuleResource;
use Filament\Resources\Pages\ListRecords;

class ListGradingRules extends ListRecords
{
  protected static string $resource = GradingRuleResource::class;

  protected function getHeaderActions(): array
  {
    return [
      \Filament\Actions\CreateAction::make(),
    ];
  }
}
