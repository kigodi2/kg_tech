<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CombinationResource\Pages;
use App\Filament\Admin\Resources\CombinationResource\RelationManagers;
use App\Models\Combination;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CombinationResource extends Resource
{
    protected static ?string $model = Combination::class;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $navigationGroup = 'Academic';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Combination Information')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('exam_type_id')
                            ->relationship('examType', 'code')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(20),
                        Forms\Components\TextInput::make('category')
                            ->maxLength(100),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('examType.code')
                    ->label('Exam Type')
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->sortable(),
                Tables\Columns\TextColumn::make('subjects_count')
                    ->label('Subjects')
                    ->counts('subjects')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
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
            ->defaultSort('code');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SubjectsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCombinations::route('/'),
            'create' => Pages\CreateCombination::route('/create'),
            'view' => Pages\ViewCombination::route('/{record}'),
            'edit' => Pages\EditCombination::route('/{record}/edit'),
        ];
    }
}
