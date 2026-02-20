<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\RawMarkResource\Pages;
use App\Models\RawMark;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RawMarkResource extends Resource
{
    protected static ?string $model = RawMark::class;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    protected static ?string $slug = 'raw-marks';

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $navigationGroup = 'Mark Entry';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Candidate & Subject Information')
                    ->schema([
                        Forms\Components\TextInput::make('candidate_index_number')
                            ->label('Index Number')
                            ->readOnly(),

                        Forms\Components\TextInput::make('full_name')
                            ->label('Full Name')
                            ->readOnly(),

                        Forms\Components\Select::make('candidate_id')
                            ->label('Candidate')
                            ->relationship('candidate', 'full_name')
                            ->searchable()
                            ->preload()
                            ->disabled(),

                        Forms\Components\Select::make('subject_id')
                            ->label('Subject')
                            ->relationship('subject', 'name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Individual Paper Marks (0-100, increments of 0.5)')
                    ->schema([
                        Forms\Components\TextInput::make('paper_1_marks')
                            ->label('Paper 1')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.5),

                        Forms\Components\TextInput::make('paper_2_marks')
                            ->label('Paper 2')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.5),

                        Forms\Components\TextInput::make('paper_3_marks')
                            ->label('Paper 3')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.5),

                        Forms\Components\TextInput::make('practical_marks')
                            ->label('Practical')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.5),

                        Forms\Components\TextInput::make('project_marks')
                            ->label('Project')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.5),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('has_errors')
                            ->label('Has Errors')
                            ->disabled(),

                        Forms\Components\Textarea::make('error_messages')
                            ->label('Error Messages')
                            ->disabled()
                            ->formatStateUsing(fn($state) => is_array($state) ? implode("\n", $state) : $state),

                        Forms\Components\Toggle::make('is_locked')
                            ->label('Locked')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('candidate_index_number')
                    ->label('Index')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Candidate')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('candidate.combination')
                    ->label('Comb')
                    ->sortable(),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Subject')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('paper_1_marks')
                    ->label('P1')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('paper_2_marks')
                    ->label('P2')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('paper_3_marks')
                    ->label('P3')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('practical_marks')
                    ->label('Practical')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('project_marks')
                    ->label('Project')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\IconColumn::make('has_errors')
                    ->label('Errors')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_locked')
                    ->label('Locked')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('has_errors')
                    ->label('Has Errors'),

                Tables\Filters\TernaryFilter::make('is_locked')
                    ->label('Locked'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListRawMarks::route('/'),
            'view' => Pages\ViewRawMark::route('/{record}'),
        ];
    }
}
