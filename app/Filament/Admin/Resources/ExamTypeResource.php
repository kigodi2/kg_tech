<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ExamTypeResource\Pages;
use App\Models\ExamType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExamTypeResource extends Resource
{
    protected static ?string $model = ExamType::class;

    protected static ?string $slug = 'exam-types';

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'System Configuration';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->maxLength(10)
                            ->unique(ignoreRecord: true)
                            ->helperText('E.g., PSLE, CSEE, ACSEE'),

                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Full name of the exam type'),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->columnSpanFull()
                            ->maxLength(1000),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Exam Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('education_level')
                            ->label('Education Level')
                            ->maxLength(50),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->badge(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('education_level')
                    ->label('Education Level')
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalDescription('Deleting an exam type will affect all associated subjects, combinations, and results. This cannot be undone.'),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExamTypes::route('/'),
            'create' => Pages\CreateExamType::route('/create'),
            'view' => Pages\ViewExamType::route('/{record}'),
            'edit' => Pages\EditExamType::route('/{record}/edit'),
        ];
    }
}
