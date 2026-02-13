<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SubjectMarksResource\Pages;
use App\Models\SubjectMarks;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubjectMarksResource extends Resource
{
    protected static ?string $model = SubjectMarks::class;

    protected static ?string $slug = 'subject-marks';

    protected static ?string $navigationIcon = 'heroicon-o-pencil';

    protected static ?string $navigationGroup = 'Mark Entry';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Mark Details')
                    ->schema([
                        Forms\Components\Select::make('candidate_id')
                            ->label('Candidate')
                            ->relationship('candidate', 'full_name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('subject_id')
                            ->label('Subject')
                            ->relationship('subject', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('exam_type_id')
                            ->label('Exam Type')
                            ->relationship('examType', 'code')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('year')
                            ->label('Year')
                            ->numeric()
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Marks')
                    ->schema([
                        Forms\Components\TextInput::make('marks_obtained')
                            ->label('Marks Obtained')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.5)
                            ->required(),

                        Forms\Components\TextInput::make('max_marks')
                            ->label('Max Marks')
                            ->numeric()
                            ->default(100),

                        Forms\Components\TextInput::make('percentage')
                            ->label('Percentage')
                            ->numeric()
                            ->readOnly()
                            ->hint('Auto-calculated'),

                        Forms\Components\TextInput::make('grade')
                            ->label('Grade')
                            ->maxLength(2)
                            ->readOnly()
                            ->hint('Auto-calculated'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_locked')
                            ->label('Locked')
                            ->default(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('candidate.full_name')
                    ->label('Candidate')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Subject')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('examType.code')
                    ->label('Exam Type')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('year')
                    ->label('Year')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('marks_obtained')
                    ->label('Marks')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('percentage')
                    ->label('%')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('grade')
                    ->label('Grade')
                    ->sortable()
                    ->alignment('center'),

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
                Tables\Filters\SelectFilter::make('exam_type_id')
                    ->label('Exam Type')
                    ->relationship('examType', 'code'),

                Tables\Filters\TernaryFilter::make('is_locked')
                    ->label('Locked Status'),
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
            'index' => Pages\ListSubjectMarks::route('/'),
            'create' => Pages\CreateSubjectMarks::route('/create'),
            'view' => Pages\ViewSubjectMarks::route('/{record}'),
            'edit' => Pages\EditSubjectMarks::route('/{record}/edit'),
        ];
    }
}
