<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\GradingRuleResource\Pages;
use App\Models\GradingRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GradingRuleResource extends Resource
{
  protected static ?string $model = GradingRule::class;

  protected static ?string $navigationIcon = 'heroicon-o-bars-3-bottom-left';

  protected static ?string $navigationGroup = 'Evaluations';

  protected static ?int $navigationSort = 3;

  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Section::make('Rule Information')
          ->columns(2)
          ->schema([
            Forms\Components\Select::make('grading_profile_id')
              ->relationship('gradingProfile', 'name')
              ->required()
              ->searchable()
              ->preload(),
            Forms\Components\TextInput::make('grade')
              ->required()
              ->maxLength(2),
            Forms\Components\TextInput::make('min_marks')
              ->label('Minimum Marks')
              ->numeric()
              ->required()
              ->minValue(0),
            Forms\Components\TextInput::make('max_marks')
              ->label('Maximum Marks')
              ->numeric()
              ->required()
              ->minValue(0),
            Forms\Components\Textarea::make('description')
              ->columnSpanFull(),
          ]),
      ]);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('gradingProfile.name')
          ->label('Profile')
          ->searchable()
          ->sortable(),
        Tables\Columns\TextColumn::make('grade')
          ->searchable()
          ->sortable(),
        Tables\Columns\TextColumn::make('min_marks')
          ->label('Min Marks')
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('max_marks')
          ->label('Max Marks')
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('description')
          ->limit(50),
        Tables\Columns\TextColumn::make('created_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        Tables\Filters\SelectFilter::make('grading_profile_id')
          ->relationship('gradingProfile', 'name'),
      ])
      ->actions([
        Tables\Actions\ViewAction::make(),
        Tables\Actions\EditAction::make(),
        Tables\Actions\DeleteAction::make(),
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DeleteBulkAction::make(),
        ]),
      ])
      ->defaultSort('grade');
  }

  public static function getRelations(): array
  {
    return [
      //
    ];
  }

  public static function getPages(): array
  {
    return [
      'index' => Pages\ListGradingRules::route('/'),
      'create' => Pages\CreateGradingRule::route('/create'),
      'view' => Pages\ViewGradingRule::route('/{record}'),
      'edit' => Pages\EditGradingRule::route('/{record}/edit'),
    ];
  }
}
