<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\GradingProfileResource\Pages;
use App\Filament\Admin\Resources\GradingProfileResource\RelationManagers;
use App\Models\GradingProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class GradingProfileResource extends Resource
{
    protected static ?string $model = GradingProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Evaluations';

    protected static ?int $navigationSort = 2;

    public static function canDelete(Model $record): bool
    {
        return !$record->is_locked;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Profile Information')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('version')
                            ->maxLength(20),
                        Forms\Components\Select::make('exam_type_id')
                            ->relationship('examType', 'code')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('exam_year_id')
                            ->relationship('examYear', 'year')
                            ->required()
                            ->searchable()
                            ->preload(),
                    ]),

                Forms\Components\Section::make('Status')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                        Forms\Components\Toggle::make('is_locked')
                            ->default(false)
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('version')
                    ->sortable(),
                Tables\Columns\TextColumn::make('examType.code')
                    ->label('Exam Type')
                    ->sortable(),
                Tables\Columns\TextColumn::make('examYear.year')
                    ->label('Year')
                    ->sortable(),
                Tables\Columns\TextColumn::make('grading_rules_count')
                    ->label('Rules')
                    ->counts('gradingRules')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_locked')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('exam_type_id')
                    ->relationship('examType', 'code'),
                Tables\Filters\TernaryFilter::make('is_active'),
                Tables\Filters\TernaryFilter::make('is_locked'),
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
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\GradingRulesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGradingProfiles::route('/'),
            'create' => Pages\CreateGradingProfile::route('/create'),
            'view' => Pages\ViewGradingProfile::route('/{record}'),
            'edit' => Pages\EditGradingProfile::route('/{record}/edit'),
        ];
    }
}
